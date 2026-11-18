<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Ticketing\Support\TicketingHealth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupportHealthController extends Controller
{
    public function index(TicketingHealth $health): View
    {
        Gate::authorize('manage', SupportTicket::class);

        return view('support.health', [
            'checks' => $health->checks(),
            'failedJobs' => DB::table('failed_jobs')->latest('id')->limit(10)->get(['id', 'queue', 'failed_at', 'exception']),
        ]);
    }
}
