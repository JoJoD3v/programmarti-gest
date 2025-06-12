<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Appointment::with(['client', 'user']);

        // Default to today's appointments, but allow showing all if no date specified
        $filterDate = $request->get('date', Carbon::today()->format('Y-m-d'));
        $filterClient = $request->get('client_id');

        // Only apply date filter if explicitly provided
        if ($filterDate && $request->has('date')) {
            $query->byDate($filterDate);
        } elseif (!$request->has('date')) {
            // If no date filter is specified, show today's appointments by default
            $query->byDate(Carbon::today()->format('Y-m-d'));
        }

        if ($filterClient) {
            $query->byClient($filterClient);
        }

        $appointments = $query->orderBy('appointment_date', 'asc')->get();

        // For AJAX requests, return JSON response like PaymentController
        if ($request->ajax()) {
            Log::info('AJAX request for appointments index', [
                'date' => $filterDate,
                'client_id' => $filterClient,
                'appointments_count' => $appointments->count()
            ]);
            return response()->json([
                'html' => view('appointments.partials.table', compact('appointments'))->render(),
                'total' => $appointments->count(),
            ]);
        }

        $clients = Client::orderBy('first_name')->get();

        return view('appointments.index', compact('appointments', 'clients', 'filterDate', 'filterClient'));
    }

    /**
     * Get filtered appointments via AJAX (copied from PaymentController pattern)
     */
    public function filter(Request $request)
    {
        // Validate filter parameters
        $request->validate([
            'date' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $query = Appointment::with(['client', 'user']);

        // Apply filters consistently with index method
        $date = $request->get('date');
        $clientId = $request->get('client_id');

        // Apply date filter - if no date specified, show all appointments
        if ($date !== null && $date !== '') {
            $query->byDate($date);
        }
        // If no date filter, don't apply any date restriction to show all appointments

        // Apply client filter
        if ($clientId !== null && $clientId !== '') {
            $query->byClient($clientId);
        }

        $appointments = $query->orderBy('appointment_date', 'asc')->get();

        return response()->json([
            'html' => view('appointments.partials.table', compact('appointments'))->render(),
            'total' => $appointments->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::orderBy('first_name')->get();
        $users = User::orderBy('first_name')->get();
        
        return view('appointments.create', compact('clients', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'user_id' => 'required|exists:users,id',
            'appointment_date' => 'required|date|after:now',
            'appointment_name' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        Appointment::create($validated);

        return redirect()->route('appointments.index')
                        ->with('success', 'Appuntamento creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Appointment $appointment)
    {
        $appointment->load(['client', 'user']);
        return view('appointments.show', compact('appointment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Appointment $appointment)
    {
        $clients = Client::orderBy('first_name')->get();
        $users = User::orderBy('first_name')->get();
        
        return view('appointments.edit', compact('appointment', 'clients', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'user_id' => 'required|exists:users,id',
            'appointment_date' => 'required|date',
            'appointment_name' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:pending,completed,cancelled,absent',
        ]);

        $appointment->update($validated);

        return redirect()->route('appointments.index')
                        ->with('success', 'Appuntamento aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('appointments.index')
                        ->with('success', 'Appuntamento eliminato con successo.');
    }

    /**
     * Update appointment status via AJAX
     */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        Log::info('Status update request received', [
            'appointment_id' => $appointment->id,
            'current_status' => $appointment->status,
            'request_data' => $request->all(),
            'is_ajax' => $request->ajax()
        ]);

        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled,absent',
        ]);

        $appointment->update(['status' => $validated['status']]);

        if ($request->ajax()) {
            // Refresh the appointment to get updated attributes
            $appointment->refresh();

            Log::info('Status updated successfully', [
                'appointment_id' => $appointment->id,
                'new_status' => $appointment->status,
                'status_label' => $appointment->status_label,
                'status_color' => $appointment->status_color
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status aggiornato con successo.',
                'status_label' => $appointment->status_label,
                'status_color' => $appointment->status_color
            ]);
        }

        return redirect()->route('appointments.index')
                        ->with('success', 'Status appuntamento aggiornato con successo.');
    }

    /**
     * Mark appointment as completed
     */
    public function markCompleted(Appointment $appointment)
    {
        // Only allow completing pending appointments
        if ($appointment->status !== 'pending') {
            return redirect()->back()
                            ->with('error', 'Solo gli appuntamenti in attesa possono essere completati.');
        }

        $appointment->update(['status' => 'completed']);

        return redirect()->back()
                        ->with('success', 'Appuntamento segnato come completato.');
    }


}
