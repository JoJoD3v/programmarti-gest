<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\Project;
use App\Models\Payment;
use App\Models\Expense;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiche generali
        $stats = [
            'total_users' => User::count(),
            'total_clients' => Client::count(),
            'total_projects' => Project::count(),
            'active_projects' => Project::whereIn('status', ['planning', 'in_progress'])->count(),
            'completed_projects' => Project::where('status', 'completed')->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'overdue_payments' => Payment::where('status', 'pending')
                                        ->where('due_date', '<', now()->subDays(20))->count(),
            'total_revenue_this_month' => Payment::where('status', 'completed')
                                                ->whereMonth('paid_date', now()->month)
                                                ->whereYear('paid_date', now()->year)
                                                ->sum('amount'),
            'total_expenses_this_month' => Expense::whereMonth('expense_date', now()->month)
                                                 ->whereYear('expense_date', now()->year)
                                                 ->sum('amount'),
        ];

        // Progetti recenti
        $recent_projects = Project::with(['client', 'assignedUser'])
                                 ->orderBy('created_at', 'desc')
                                 ->limit(5)
                                 ->get();

        // Pagamenti in scadenza
        $upcoming_payments = Payment::with(['project', 'client'])
                                   ->where('status', 'pending')
                                   ->where('due_date', '>=', now())
                                   ->where('due_date', '<=', now()->addDays(30))
                                   ->orderBy('due_date')
                                   ->limit(10)
                                   ->get();

        // Pagamenti scaduti
        $overdue_payments = Payment::with(['project', 'client'])
                                  ->where('status', 'pending')
                                  ->where('due_date', '<', now()->subDays(20))
                                  ->orderBy('due_date')
                                  ->limit(10)
                                  ->get();

        return view('dashboard', compact(
            'stats',
            'recent_projects',
            'upcoming_payments',
            'overdue_payments'
        ));
    }
}
