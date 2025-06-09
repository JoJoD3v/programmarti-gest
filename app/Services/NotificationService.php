<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Payment;
use App\Models\Project;

class NotificationService
{
    /**
     * Create notification for new payment
     */
    public function notifyPaymentCreated(Payment $payment)
    {
        $users = collect();

        // Notify admin users
        $adminUsers = User::role('admin')->get();
        $users = $users->merge($adminUsers);

        // Notify assigned user if exists
        if ($payment->assignedUser) {
            $users->push($payment->assignedUser);
        }

        // Remove duplicates
        $users = $users->unique('id');

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'payment_created',
                'title' => 'Nuovo Pagamento Creato',
                'message' => "È stato creato un nuovo pagamento di €" . number_format($payment->amount, 2) . " per il progetto \"{$payment->project->name}\"",
                'data' => [
                    'payment_id' => $payment->id,
                    'project_id' => $payment->project_id,
                    'client_id' => $payment->client_id,
                    'amount' => $payment->amount,
                ]
            ]);
        }
    }

    /**
     * Create notification for project assignment
     */
    public function notifyProjectAssigned(Project $project, User $assignedUser)
    {
        Notification::create([
            'user_id' => $assignedUser->id,
            'type' => 'project_assigned',
            'title' => 'Progetto Assegnato',
            'message' => "Ti è stato assegnato il progetto \"{$project->name}\" per il cliente {$project->client->full_name}",
            'data' => [
                'project_id' => $project->id,
                'client_id' => $project->client_id,
                'project_name' => $project->name,
                'client_name' => $project->client->full_name,
            ]
        ]);
    }

    /**
     * Create notification for payment due soon
     */
    public function notifyPaymentDue(Payment $payment)
    {
        $users = collect();

        // Notify admin users
        $adminUsers = User::role('admin')->get();
        $users = $users->merge($adminUsers);

        // Notify assigned user if exists
        if ($payment->assignedUser) {
            $users->push($payment->assignedUser);
        }

        // Remove duplicates
        $users = $users->unique('id');

        $daysUntilDue = now()->diffInDays($payment->due_date, false);
        $dueText = $daysUntilDue > 0 ? "tra {$daysUntilDue} giorni" : "oggi";

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'payment_due',
                'title' => 'Pagamento in Scadenza',
                'message' => "Il pagamento di €" . number_format($payment->amount, 2) . " per il progetto \"{$payment->project->name}\" scade {$dueText}",
                'data' => [
                    'payment_id' => $payment->id,
                    'project_id' => $payment->project_id,
                    'client_id' => $payment->client_id,
                    'amount' => $payment->amount,
                    'due_date' => $payment->due_date->format('Y-m-d'),
                    'days_until_due' => $daysUntilDue,
                ]
            ]);
        }
    }

    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()->unread()->update(['read_at' => now()]);
    }

    /**
     * Get recent notifications for user
     */
    public function getRecentNotifications(User $user, int $limit = 5)
    {
        return $user->notifications()
                   ->orderBy('created_at', 'desc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Clean old read notifications (older than 30 days)
     */
    public function cleanOldNotifications(): void
    {
        Notification::whereNotNull('read_at')
                   ->where('read_at', '<', now()->subDays(30))
                   ->delete();
    }
}
