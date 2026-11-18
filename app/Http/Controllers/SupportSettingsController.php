<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\TicketingSettingLog;
use App\Models\User;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportSettingsController extends Controller
{
    public function edit(TicketingSettings $settings): View
    {
        Gate::authorize('manage', SupportTicket::class);

        return view('support.settings.edit', [
            'settings' => $settings->all(),
            'users' => User::query()->ticketingEnabled()->orWhere(fn ($q) => $q->active()->whereIn('role', ['amministratore', 'sviluppatore']))->orderBy('last_name')->get(),
            'logs' => TicketingSettingLog::query()->with('user')->latest('id')->limit(20)->get(),
            'agents' => User::query()->ticketingEnabled()->orderBy('last_name')->get(),
        ]);
    }

    public function updateShares(Request $request, TicketingSettings $settings): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $agentIds = User::query()->ticketingEnabled()->pluck('id')->all();

        $data = $request->validate([
            'shares' => ['required', 'array'],
            'shares.*' => ['required', 'integer', 'between:0,100'],
        ]);

        $shares = collect($data['shares'])->mapWithKeys(fn ($share, $id) => [(int) $id => (int) $share])->only($agentIds);

        if ($shares->sum() !== 100) {
            return back()->withErrors(['shares' => 'Il totale delle percentuali deve essere 100%.'])->withInput();
        }

        $settings->setMany([
            'assignment_shares' => $shares->all(),
            'assignment_shares_since' => now()->toIso8601String(),
        ], $request->user());

        return back()->with('success', 'Percentuali di assegnazione salvate: il conteggio riparte da adesso.');
    }

    public function resetShares(Request $request, TicketingSettings $settings): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $settings->setMany(['assignment_shares' => [], 'assignment_shares_since' => null], $request->user());

        return back()->with('success', 'Percentuali disattivate: i ticket vanno a chi ne ha meno aperti.');
    }

    public function update(Request $request, TicketingSettings $settings): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $data = $request->validate([
            'personal_saved_replies_enabled' => ['boolean'],
            'llm_classifier_enabled' => ['boolean'],
            'drafts_enabled' => ['boolean'],
            'drafts_audience' => ['required', Rule::in(['admins', 'selected_users', 'all'])],
            'drafts_selected_user_ids' => ['nullable', 'array'],
            'drafts_selected_user_ids.*' => ['integer', 'exists:users,id'],
            'drafts_pregenerate' => ['boolean'],
            'drafts_tone_rules' => ['nullable', 'string', 'max:3000'],
            'reply_signature' => ['nullable', 'string', 'max:1000'],
            'gmail_trash_after_hours' => ['nullable', 'integer', 'between:1,8760'],
        ]);

        $draftsEnabled = $request->boolean('drafts_enabled');

        $settings->setMany([
            'personal_saved_replies_enabled' => $request->boolean('personal_saved_replies_enabled'),
            'llm_classifier_enabled' => $request->boolean('llm_classifier_enabled'),
            'drafts_enabled' => $draftsEnabled,
            'drafts_audience' => $data['drafts_audience'],
            'drafts_selected_user_ids' => array_map('intval', $data['drafts_selected_user_ids'] ?? []),
            'drafts_pregenerate' => $draftsEnabled && $request->boolean('drafts_pregenerate'), // only with drafts on
            'drafts_tone_rules' => (string) ($data['drafts_tone_rules'] ?? ''),
            'reply_signature' => (string) ($data['reply_signature'] ?? ''),
            'gmail_trash_after_hours' => isset($data['gmail_trash_after_hours']) ? (int) $data['gmail_trash_after_hours'] : null,
        ], $request->user());

        return back()->with('success', 'Impostazioni salvate.');
    }
}
