<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Work;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class WorkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Work::with(['project.client', 'assignedUser']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('project', function($projectQuery) use ($search) {
                      $projectQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('assignedUser', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by project - only apply filter if a specific project is selected
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status - only apply filter if a specific status is selected
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by work type - only apply filter if a specific type is selected
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $works = $query->orderBy('created_at', 'desc')->paginate(15);
        $projects = Project::orderBy('name')->get();

        // Debug information (remove in production)
        if (config('app.debug')) {
            Log::info('Works Filter Debug', [
                'search' => $request->search,
                'project_id' => $request->project_id,
                'status' => $request->status,
                'type' => $request->type,
                'total_works' => $works->total(),
                'sql' => $query->toSql()
            ]);
        }

        return view('works.index', compact('works', 'projects'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('first_name')->get();
        $selectedProjectId = $request->get('project_id');

        return view('works.create', compact('projects', 'users', 'selectedProjectId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Bug,Miglioramenti,Da fare',
            'assigned_user_id' => 'required|exists:users,id',
        ]);

        // Auto-set status to 'In Sospeso' when creating
        $validated['status'] = 'In Sospeso';

        Work::create($validated);

        return redirect()->route('works.index')
                        ->with('success', 'Lavoro creato con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Work $work)
    {
        $work->load(['project', 'assignedUser']);
        return view('works.show', compact('work'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Work $work)
    {
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('first_name')->get();

        return view('works.edit', compact('work', 'projects', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Work $work)
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:Bug,Miglioramenti,Da fare',
            'assigned_user_id' => 'required|exists:users,id',
            'status' => 'required|in:In Sospeso,Completato',
        ]);

        $work->update($validated);

        return redirect()->route('works.index')
                        ->with('success', 'Lavoro aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Work $work)
    {
        $work->delete();

        return redirect()->route('works.index')
                        ->with('success', 'Lavoro eliminato con successo.');
    }

    /**
     * Update work status to completed
     */
    public function markCompleted(Work $work)
    {
        $work->update(['status' => 'Completato']);

        return redirect()->route('works.index')
                        ->with('success', 'Lavoro contrassegnato come completato.');
    }
}
