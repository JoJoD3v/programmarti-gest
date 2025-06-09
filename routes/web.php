<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Management Routes
    Route::resource('users', UserController::class)->middleware('permission:manage users');

    // Client Management Routes
    Route::resource('clients', ClientController::class)->middleware('permission:manage clients');

    // Project Management Routes
    Route::resource('projects', ProjectController::class)->middleware('permission:manage projects');
    Route::post('projects/{project}/generate-payments', [ProjectController::class, 'generatePayments'])
        ->name('projects.generate-payments')
        ->middleware('permission:manage projects');

    // Payment Management Routes
    Route::resource('payments', PaymentController::class)->middleware('permission:manage payments');
    Route::patch('payments/{payment}/mark-completed', [PaymentController::class, 'markCompleted'])
        ->name('payments.mark-completed')
        ->middleware('permission:manage payments');
    Route::get('payments/{payment}/invoice', [PaymentController::class, 'generateInvoice'])
        ->name('payments.invoice')
        ->middleware('permission:generate invoices');
    Route::post('payments/{payment}/send-invoice', [PaymentController::class, 'sendInvoice'])
        ->name('payments.send-invoice')
        ->middleware('permission:send emails');

    // Expense Management Routes
    Route::resource('expenses', ExpenseController::class)->middleware('permission:manage expenses');

    // Notification Routes
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::get('notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unread-count');
    Route::get('notifications/recent', [NotificationController::class, 'getRecent'])->name('notifications.recent');
});

// Broadcast routes (outside auth middleware)
Broadcast::routes(['middleware' => ['auth']]);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
