<?php

use App\Http\Controllers\SupportAssignmentController;
use App\Http\Controllers\SupportGmailTrashController;
use App\Http\Controllers\SupportHealthController;
use App\Http\Controllers\SupportKnowledgeController;
use App\Http\Controllers\SupportMailboxController;
use App\Http\Controllers\SupportMailboxGoogleController;
use App\Http\Controllers\SupportSavedReplyController;
use App\Http\Controllers\SupportSenderRuleController;
use App\Http\Controllers\SupportSettingsController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\SupportTypeController;
use Illuminate\Support\Facades\Route;

/*
| Email ticketing (ported from Noli Gestionale). Loaded inside the authenticated group of web.php;
| access is decided by SupportTicketPolicy (agents: users.can_manage_tickets, admins: "manage users").
*/

Route::prefix('assistenza')->group(function () {
    Route::get('/', [SupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('nuovo', [SupportTicketController::class, 'create'])->name('support-tickets.create');
    Route::post('/', [SupportTicketController::class, 'store'])->name('support-tickets.store');
    Route::post('bulk', [SupportTicketController::class, 'bulk'])->name('support-tickets.bulk');
    Route::get('allegati/{attachment}', [SupportTicketController::class, 'downloadAttachment'])->name('support-attachments.download');

    // gestione (admin) e strumenti comuni
    Route::prefix('gestione')->name('support.')->group(function () {
        Route::get('assegnazioni', [SupportAssignmentController::class, 'index'])->name('assignments.index');
        Route::patch('assegnazioni/{user}/disponibilita', [SupportAssignmentController::class, 'toggleAvailability'])->name('assignments.availability');
        Route::post('assegnazioni/{user}/ridistribuisci', [SupportAssignmentController::class, 'redistribute'])->name('assignments.redistribute');

        Route::get('impostazioni', [SupportSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('impostazioni', [SupportSettingsController::class, 'update'])->name('settings.update');
        Route::put('impostazioni/percentuali', [SupportSettingsController::class, 'updateShares'])->name('settings.shares');
        Route::delete('impostazioni/percentuali', [SupportSettingsController::class, 'resetShares'])->name('settings.shares.reset');

        // the Google callback URL is fixed (registered on Google Cloud): it must stay before caselle/{mailbox}
        Route::get('caselle/google/callback', [SupportMailboxGoogleController::class, 'callback'])->name('mailboxes.google.callback');
        Route::resource('caselle', SupportMailboxController::class)->except(['show'])->parameters(['caselle' => 'mailbox'])->names('mailboxes');
        Route::post('caselle/{mailbox}/riattiva', [SupportMailboxController::class, 'reactivate'])->name('mailboxes.reactivate');
        Route::get('caselle/{mailbox}/google', [SupportMailboxGoogleController::class, 'redirect'])->name('mailboxes.google.redirect');
        Route::delete('caselle/{mailbox}/google', [SupportMailboxGoogleController::class, 'destroy'])->name('mailboxes.google.destroy');

        Route::get('cestino-gmail', [SupportGmailTrashController::class, 'index'])->name('gmail-trash.index');
        Route::post('cestino-gmail/{mailbox}/{thread}/ripristina', [SupportGmailTrashController::class, 'restore'])->whereNumber('thread')->name('gmail-trash.restore');

        Route::get('stato', [SupportHealthController::class, 'index'])->name('health');

        Route::resource('tipologie', SupportTypeController::class)->except(['show'])->parameters(['tipologie' => 'type'])->names('types');
        Route::resource('regole', SupportSenderRuleController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['regole' => 'rule'])->names('rules');
        Route::resource('conoscenza', SupportKnowledgeController::class)->except(['show'])->parameters(['conoscenza' => 'entry'])->names('knowledge');
        Route::resource('risposte-salvate', SupportSavedReplyController::class)->except(['show'])->parameters(['risposte-salvate' => 'reply'])->names('saved-replies');
        Route::post('risposte-salvate/{reply}/duplica', [SupportSavedReplyController::class, 'duplicate'])->name('saved-replies.duplicate');
        Route::post('risposte-salvate/{reply}/promuovi', [SupportSavedReplyController::class, 'promote'])->name('saved-replies.promote');
        Route::post('risposte-salvate/da-testo', [SupportSavedReplyController::class, 'fromText'])->name('saved-replies.from-text');
    });

    Route::prefix('{supportTicket}')->whereNumber('supportTicket')->name('support-tickets.')->group(function () {
        Route::get('/', [SupportTicketController::class, 'show'])->name('show');
        Route::get('stampa', [SupportTicketController::class, 'print'])->name('print');
        Route::post('risposta', [SupportTicketController::class, 'reply'])->name('reply');
        Route::post('messaggi/{message}/riprova', [SupportTicketController::class, 'retry'])->name('retry');
        Route::post('chiudi', [SupportTicketController::class, 'close'])->name('close');
        Route::patch('tipologia', [SupportTicketController::class, 'changeType'])->name('type');
        Route::patch('cliente', [SupportTicketController::class, 'changeClient'])->name('client');
        Route::patch('assegna', [SupportTicketController::class, 'assign'])->name('assign');
        Route::put('bozza-testo', [SupportTicketController::class, 'saveDraft'])->name('draft');
        Route::post('regola-mittente', [SupportTicketController::class, 'createSenderRule'])->name('sender-rule');
        Route::post('bozza-ai', [SupportTicketController::class, 'generateDraft'])->name('ai-draft');
        Route::patch('bozza-ai/{draft}', [SupportTicketController::class, 'draftFeedback'])->name('ai-draft.feedback');
    });
});
