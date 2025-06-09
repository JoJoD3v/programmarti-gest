<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Events\PaymentCreated;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['project', 'client', 'assignedUser']);

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by month
        if ($request->has('month') && $request->month !== '') {
            $query->whereMonth('due_date', $request->month);
        }

        // Filter by year
        if ($request->has('year') && $request->year !== '') {
            $query->whereYear('due_date', $request->year);
        }

        $payments = $query->orderBy('due_date', 'desc')->paginate(15);

        return view('payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projects = Project::with('client')->get();
        $clients = Client::all();
        return view('payments.create', compact('projects', 'clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'client_id' => 'required|exists:clients,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'payment_type' => 'required|in:down_payment,installment,one_time',
            'notes' => 'nullable|string',
        ]);

        $payment = Payment::create($validated);

        // Fire event for notifications
        event(new PaymentCreated($payment));

        return redirect()->route('payments.index')
                        ->with('success', 'Pagamento creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        $payment->load(['project', 'client', 'assignedUser']);
        return view('payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        $projects = Project::with('client')->get();
        $clients = Client::all();
        return view('payments.edit', compact('payment', 'projects', 'clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'client_id' => 'required|exists:clients,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'payment_type' => 'required|in:down_payment,installment,one_time',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);

        return redirect()->route('payments.index')
                        ->with('success', 'Pagamento aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()->route('payments.index')
                        ->with('success', 'Pagamento eliminato con successo.');
    }

    /**
     * Mark payment as completed
     */
    public function markCompleted(Payment $payment)
    {
        $payment->markAsCompleted();

        // Generate invoice number if not exists
        if (!$payment->invoice_number) {
            $payment->update(['invoice_number' => $payment->generateInvoiceNumber()]);
        }

        return redirect()->back()
                        ->with('success', 'Pagamento segnato come completato.');
    }

    /**
     * Generate PDF invoice
     */
    public function generateInvoice(Payment $payment)
    {
        if ($payment->status !== 'completed') {
            return redirect()->back()
                            ->with('error', 'È possibile generare fatture solo per pagamenti completati.');
        }

        // Generate invoice number if not exists
        if (!$payment->invoice_number) {
            $payment->update(['invoice_number' => $payment->generateInvoiceNumber()]);
        }

        $pdf = Pdf::loadView('payments.invoice', compact('payment'));

        $filename = "Fattura_{$payment->invoice_number}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Send invoice via email
     */
    public function sendInvoice(Payment $payment)
    {
        if ($payment->status !== 'completed') {
            return redirect()->back()
                            ->with('error', 'È possibile inviare fatture solo per pagamenti completati.');
        }

        // Generate invoice number if not exists
        if (!$payment->invoice_number) {
            $payment->update(['invoice_number' => $payment->generateInvoiceNumber()]);
        }

        try {
            // Generate PDF
            $pdf = Pdf::loadView('payments.invoice', compact('payment'));
            $filename = "Fattura_{$payment->invoice_number}.pdf";

            // Send email with PDF attachment
            Mail::send('emails.invoice', compact('payment'), function ($message) use ($payment, $pdf, $filename) {
                $message->to($payment->client->email, $payment->client->full_name)
                        ->subject("Fattura #{$payment->invoice_number} - {$payment->project->name}")
                        ->attachData($pdf->output(), $filename, [
                            'mime' => 'application/pdf',
                        ]);
            });

            return redirect()->back()
                            ->with('success', 'Fattura inviata con successo via email.');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->with('error', 'Errore nell\'invio della fattura: ' . $e->getMessage());
        }
    }
}
