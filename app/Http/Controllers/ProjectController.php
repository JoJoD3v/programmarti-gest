<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Client;
use App\Models\User;
use App\Models\Payment;
use App\Events\ProjectAssigned;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Project::with(['client', 'assignedUser']);

        // Search functionality
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('client', function($clientQuery) use ($search) {
                      $clientQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by project type
        if ($request->has('project_type') && $request->project_type !== '') {
            $query->where('project_type', $request->project_type);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $clients = Client::orderBy('first_name')->get();
        $users = User::orderBy('first_name')->get();
        $selectedClientId = $request->get('client_id');

        return view('projects.create', compact('clients', 'users', 'selectedClientId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'required|in:website,ecommerce,management_system,marketing_campaign,social_media_management,nfc_accessories',
            'client_id' => 'required|exists:clients,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'payment_type' => 'required|in:one_time,installments',
            'total_cost' => 'nullable|numeric|min:0',
            'has_down_payment' => 'boolean',
            'down_payment_amount' => 'nullable|numeric|min:0',
            'payment_frequency' => 'nullable|in:monthly,quarterly,yearly',
            'installment_amount' => 'nullable|numeric|min:0',
            'installment_count' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:planning,in_progress,completed,cancelled',
        ]);

        $project = Project::create($validated);

        // Fire event if user is assigned
        if ($validated['assigned_user_id']) {
            $assignedUser = User::find($validated['assigned_user_id']);
            event(new ProjectAssigned($project, $assignedUser));
        }

        // Generate payments if needed
        if ($request->has('generate_payments') && $request->generate_payments) {
            $project->generatePayments();
        }

        return redirect()->route('projects.index')
                        ->with('success', 'Progetto creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $project->load(['client', 'assignedUser', 'payments']);
        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $clients = Client::orderBy('first_name')->get();
        $users = User::orderBy('first_name')->get();

        return view('projects.edit', compact('project', 'clients', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'required|in:website,ecommerce,management_system,marketing_campaign,social_media_management,nfc_accessories',
            'client_id' => 'required|exists:clients,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'payment_type' => 'required|in:one_time,installments',
            'total_cost' => 'nullable|numeric|min:0',
            'has_down_payment' => 'boolean',
            'down_payment_amount' => 'nullable|numeric|min:0',
            'payment_frequency' => 'nullable|in:monthly,quarterly,yearly',
            'installment_amount' => 'nullable|numeric|min:0',
            'installment_count' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|in:planning,in_progress,completed,cancelled',
        ]);

        // Check if assigned user changed
        $oldAssignedUserId = $project->assigned_user_id;
        $newAssignedUserId = $validated['assigned_user_id'];

        $project->update($validated);

        // Fire event if user assignment changed
        if ($newAssignedUserId && $newAssignedUserId !== $oldAssignedUserId) {
            $assignedUser = User::find($newAssignedUserId);
            event(new ProjectAssigned($project, $assignedUser));
        }

        return redirect()->route('projects.index')
                        ->with('success', 'Progetto aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')
                        ->with('success', 'Progetto eliminato con successo.');
    }

    /**
     * Generate payments for the project
     */
    public function generatePayments(Project $project)
    {
        $project->generatePayments();

        return redirect()->route('projects.show', $project)
                        ->with('success', 'Pagamenti generati con successo.');
    }
}
