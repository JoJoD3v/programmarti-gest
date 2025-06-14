<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\WorkController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PreventivoController;
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
    Route::get('payments-filter', [PaymentController::class, 'filter'])
        ->name('payments.filter')
        ->middleware('permission:manage payments');
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

    // Appointment Management Routes
    Route::resource('appointments', AppointmentController::class);
    Route::get('appointments-filter', [AppointmentController::class, 'filter'])
        ->name('appointments.filter');
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])
        ->name('appointments.update-status');
    Route::post('appointments/{appointment}/mark-completed', [AppointmentController::class, 'markCompleted'])
        ->name('appointments.mark-completed');


    // Work Management Routes
    Route::resource('works', WorkController::class);
    Route::patch('works/{work}/mark-completed', [WorkController::class, 'markCompleted'])
        ->name('works.mark-completed');

    // Preventivi (Quotes) Management Routes
    Route::resource('preventivi', PreventivoController::class)->parameters([
        'preventivi' => 'preventivo'
    ]);
    Route::get('api/clients/{client}/projects', [PreventivoController::class, 'getProjectsByClient'])
        ->name('api.clients.projects');
    Route::post('preventivi/{preventivo}/enhance-ai', [PreventivoController::class, 'enhanceWithAI'])
        ->name('preventivi.enhance-ai');
    Route::post('preventivi/{preventivo}/generate-pdf', [PreventivoController::class, 'generatePDF'])
        ->name('preventivi.generate-pdf');
    Route::get('preventivi/{preventivo}/download-pdf', [PreventivoController::class, 'downloadPDF'])
        ->name('preventivi.download-pdf');

});

// Broadcast routes (outside auth middleware)
Broadcast::routes(['middleware' => ['auth']]);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
