<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Services\NotificationService;
use Carbon\Carbon;

class CheckDuePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:check-due';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for payments due in 7 days and send notifications';

    protected NotificationService $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for payments due in 7 days...');

        // Get payments due in 7 days
        $dueDate = Carbon::now()->addDays(7)->toDateString();

        $duePayments = Payment::where('status', 'pending')
                             ->whereDate('due_date', $dueDate)
                             ->with(['project', 'client', 'assignedUser'])
                             ->get();

        if ($duePayments->isEmpty()) {
            $this->info('No payments due in 7 days.');
            return;
        }

        $this->info("Found {$duePayments->count()} payments due in 7 days.");

        foreach ($duePayments as $payment) {
            $this->notificationService->notifyPaymentDue($payment);
            $this->line("Notification sent for payment #{$payment->id} - {$payment->project->name}");
        }

        $this->info('Payment due notifications sent successfully.');
    }
}
