<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\User;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::with('user');

        // Filter by user
        if ($request->has('user_id') && $request->user_id !== '') {
            $query->where('user_id', $request->user_id);
        }

        // Filter by category
        if ($request->has('category') && $request->category !== '') {
            $query->where('category', $request->category);
        }

        // Filter by month
        if ($request->has('month') && $request->month !== '') {
            $query->whereMonth('expense_date', $request->month);
        }

        // Filter by year
        if ($request->has('year') && $request->year !== '') {
            $query->whereYear('expense_date', $request->year);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(15);
        $users = User::orderBy('first_name')->get();

        return view('expenses.index', compact('expenses', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::orderBy('first_name')->get();
        return view('expenses.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'expense_date' => 'required|date',
            'category' => 'nullable|string',
        ]);

        Expense::create($validated);

        return redirect()->route('expenses.index')
                        ->with('success', 'Spesa creata con successo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        $expense->load('user');
        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        $users = User::orderBy('first_name')->get();
        return view('expenses.edit', compact('expense', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string',
            'expense_date' => 'required|date',
            'category' => 'nullable|string',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')
                        ->with('success', 'Spesa aggiornata con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')
                        ->with('success', 'Spesa eliminata con successo.');
    }
}
