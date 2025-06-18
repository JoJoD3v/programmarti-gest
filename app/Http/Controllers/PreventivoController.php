<?php

namespace App\Http\Controllers;

use App\Models\Preventivo;
use App\Models\PreventivoItem;
use App\Models\Client;
use App\Models\Project;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PreventivoController extends Controller
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Preventivo::with(['client', 'project']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('project', function ($projectQuery) use ($search) {
                      $projectQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $preventivi = $query->orderBy('created_at', 'desc')->paginate(15);
        $clients = Client::orderBy('first_name')->get();

        // For AJAX requests, return only the table content
        if ($request->ajax()) {
            return response()->json([
                'html' => view('preventivi.partials.table', compact('preventivi'))->render(),
                'pagination' => view('pagination.custom', compact('preventivi'))->render(),
                'total' => $preventivi->total(),
            ]);
        }

        return view('preventivi.index', compact('preventivi', 'clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Client::orderBy('first_name')->get();
        return view('preventivi.create', compact('clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'required|exists:projects,id',
            'description' => 'required|string',
            'work_items' => 'required|array|min:1',
            'work_items.*.description' => 'required|string',
            'work_items.*.cost' => 'required|numeric|min:0',
            'vat_enabled' => 'boolean',
            'vat_rate' => 'numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Create the preventivo
            $preventivo = Preventivo::create([
                'quote_number' => Preventivo::generateQuoteNumber(),
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'],
                'description' => $validated['description'],
                'vat_enabled' => $validated['vat_enabled'] ?? false,
                'vat_rate' => $validated['vat_rate'] ?? 22.00,
                'status' => 'draft',
            ]);

            // Create work items
            foreach ($validated['work_items'] as $item) {
                PreventivoItem::create([
                    'preventivo_id' => $preventivo->id,
                    'description' => $item['description'],
                    'cost' => $item['cost'],
                ]);
            }

            // Calculate total
            $preventivo->calculateTotal();

            // Refresh the model to get updated timestamps
            $preventivo->refresh();

            DB::commit();

            return redirect()->route('preventivi.show', $preventivo)
                           ->with('success', 'Preventivo creato con successo.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating preventivo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $validated
            ]);

            return back()->withInput()
                        ->with('error', 'Errore durante la creazione del preventivo: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Preventivo $preventivo)
    {
        $preventivo->load(['client', 'project', 'items']);
        return view('preventivi.show', compact('preventivo'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Preventivo $preventivo)
    {
        $preventivo->load(['items']);
        $clients = Client::orderBy('first_name')->get();
        $projects = Project::where('client_id', $preventivo->client_id)->get();

        return view('preventivi.edit', compact('preventivo', 'clients', 'projects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Preventivo $preventivo)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'required|exists:projects,id',
            'description' => 'required|string',
            'status' => 'required|in:draft,sent,accepted,rejected',
            'work_items' => 'required|array|min:1',
            'work_items.*.description' => 'required|string',
            'work_items.*.cost' => 'required|numeric|min:0',
            'vat_enabled' => 'boolean',
            'vat_rate' => 'numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Log VAT settings before update for debugging
            Log::info('Preventivo update - VAT settings before', [
                'preventivo_id' => $preventivo->id,
                'current_vat_enabled' => $preventivo->vat_enabled,
                'current_vat_rate' => $preventivo->vat_rate,
                'form_vat_enabled' => $validated['vat_enabled'] ?? 'not_set',
                'form_vat_rate' => $validated['vat_rate'] ?? 'not_set',
                'validated_data' => $validated
            ]);

            // Properly handle VAT checkbox (ensure boolean conversion)
            $vatEnabled = isset($validated['vat_enabled']) && $validated['vat_enabled'];
            $vatRate = $validated['vat_rate'] ?? 22.00;

            // Update preventivo
            $preventivo->update([
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'vat_enabled' => $vatEnabled,
                'vat_rate' => $vatRate,
            ]);

            // Log VAT settings after update for debugging
            Log::info('Preventivo update - VAT settings after', [
                'preventivo_id' => $preventivo->id,
                'updated_vat_enabled' => $preventivo->vat_enabled,
                'updated_vat_rate' => $preventivo->vat_rate
            ]);

            // Delete existing items and create new ones
            $preventivo->items()->delete();

            foreach ($validated['work_items'] as $item) {
                PreventivoItem::create([
                    'preventivo_id' => $preventivo->id,
                    'description' => $item['description'],
                    'cost' => $item['cost'],
                ]);
            }

            // Log totals before recalculation
            Log::info('Preventivo update - Totals before calculateTotal', [
                'preventivo_id' => $preventivo->id,
                'subtotal_amount' => $preventivo->subtotal_amount,
                'vat_enabled' => $preventivo->vat_enabled,
                'vat_rate' => $preventivo->vat_rate,
                'vat_amount' => $preventivo->vat_amount,
                'total_amount' => $preventivo->total_amount,
                'items_count' => $preventivo->items()->count(),
                'items_sum' => $preventivo->items()->sum('cost')
            ]);

            // Calculate total
            $preventivo->calculateTotal();

            // Log totals after recalculation
            Log::info('Preventivo update - Totals after calculateTotal', [
                'preventivo_id' => $preventivo->id,
                'final_subtotal_amount' => $preventivo->subtotal_amount,
                'final_vat_enabled' => $preventivo->vat_enabled,
                'final_vat_rate' => $preventivo->vat_rate,
                'final_vat_amount' => $preventivo->vat_amount,
                'final_total_amount' => $preventivo->total_amount
            ]);

            DB::commit();

            return redirect()->route('preventivi.show', $preventivo)
                           ->with('success', 'Preventivo aggiornato con successo.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating preventivo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'preventivo_id' => $preventivo->id,
                'data' => $validated
            ]);

            return back()->withInput()
                        ->with('error', 'Errore durante l\'aggiornamento del preventivo: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Preventivo $preventivo)
    {
        try {
            // Delete PDF file if exists
            if ($preventivo->pdf_path && Storage::exists($preventivo->pdf_path)) {
                Storage::delete($preventivo->pdf_path);
            }

            $preventivo->delete();

            return redirect()->route('preventivi.index')
                           ->with('success', 'Preventivo eliminato con successo.');
        } catch (\Exception $e) {
            Log::error('Error deleting preventivo', [
                'error' => $e->getMessage(),
                'preventivo_id' => $preventivo->id
            ]);

            return back()->with('error', 'Errore durante l\'eliminazione del preventivo.');
        }
    }

    /**
     * Get projects by client via AJAX
     */
    public function getProjectsByClient(Client $client)
    {
        $projects = $client->projects()->orderBy('name')->get();

        return response()->json([
            'projects' => $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                ];
            })
        ]);
    }

    /**
     * Enhance work items with AI
     */
    public function enhanceWithAI(Preventivo $preventivo)
    {
        try {
            // Check if already processed
            if ($preventivo->ai_processed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questo preventivo è già stato analizzato con AI.'
                ], 400);
            }

            $preventivo->load('items');

            // Check if there are items to enhance
            if ($preventivo->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessuna voce di lavoro trovata per l\'analisi AI.'
                ], 400);
            }

            Log::info('Starting AI enhancement', [
                'preventivo_id' => $preventivo->id,
                'items_count' => $preventivo->items->count()
            ]);

            $workItems = $preventivo->items->map(function ($item) {
                return [
                    'description' => $item->description,
                    'cost' => $item->cost,
                ];
            })->toArray();

            $enhancedItems = $this->openAIService->enhanceWorkItems(
                $preventivo->description,
                $workItems
            );

            // Update items with AI enhanced descriptions
            $updatedCount = 0;
            foreach ($enhancedItems as $index => $enhanced) {
                if (isset($preventivo->items[$index])) {
                    $item = $preventivo->items[$index];
                    $item->update([
                        'ai_enhanced_description' => $enhanced['ai_enhanced_description']
                    ]);
                    $updatedCount++;
                }
            }

            $preventivo->update(['ai_processed' => true]);

            // Log totals before recalculation for debugging
            Log::info('AI Enhancement - Totals before recalculation', [
                'preventivo_id' => $preventivo->id,
                'subtotal_amount' => $preventivo->subtotal_amount,
                'vat_enabled' => $preventivo->vat_enabled,
                'vat_rate' => $preventivo->vat_rate,
                'vat_amount' => $preventivo->vat_amount,
                'total_amount' => $preventivo->total_amount,
                'items_count' => $preventivo->items()->count(),
                'items_sum' => $preventivo->items()->sum('cost')
            ]);

            // Recalculate totals to ensure VAT is properly applied
            $preventivo->calculateTotal();

            Log::info('AI enhancement completed', [
                'preventivo_id' => $preventivo->id,
                'items_updated' => $updatedCount,
                'totals_recalculated' => true,
                'final_subtotal_amount' => $preventivo->subtotal_amount,
                'final_vat_amount' => $preventivo->vat_amount,
                'final_total_amount' => $preventivo->total_amount
            ]);

            // Refresh the model to get updated totals
            $preventivo->refresh();

            return response()->json([
                'success' => true,
                'message' => "Analisi AI completata con successo. {$updatedCount} descrizioni sono state migliorate.",
                'items' => $enhancedItems,
                'updated_count' => $updatedCount,
                'totals' => [
                    'subtotal_amount' => $preventivo->subtotal_amount,
                    'vat_enabled' => $preventivo->vat_enabled,
                    'vat_rate' => $preventivo->vat_rate,
                    'vat_amount' => $preventivo->vat_amount,
                    'total_amount' => $preventivo->total_amount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error enhancing with AI', [
                'error' => $e->getMessage(),
                'preventivo_id' => $preventivo->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'analisi con AI. Riprova più tardi.'
            ], 500);
        }
    }

    /**
     * Generate PDF for the preventivo
     */
    public function generatePDF(Preventivo $preventivo)
    {
        try {
            $preventivo->load(['client', 'project', 'items']);

            $pdf = Pdf::loadView('preventivi.pdf', compact('preventivo'));

            $filename = "preventivo_{$preventivo->quote_number}.pdf";
            $path = "preventivi/{$filename}";

            // Save PDF to storage
            Storage::put($path, $pdf->output());

            // Update preventivo with PDF path
            $preventivo->update(['pdf_path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'PDF salvato con successo. Ora puoi scaricarlo.',
                'download_url' => route('preventivi.download-pdf', $preventivo)
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating PDF', [
                'error' => $e->getMessage(),
                'preventivo_id' => $preventivo->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio del PDF. Riprova più tardi.'
            ], 500);
        }
    }

    /**
     * Download PDF
     */
    public function downloadPDF(Preventivo $preventivo)
    {
        if (!$preventivo->pdf_path || !Storage::exists($preventivo->pdf_path)) {
            return back()->with('error', 'PDF non trovato.');
        }

        return Storage::download($preventivo->pdf_path, "preventivo_{$preventivo->quote_number}.pdf");
    }
}
