<?php

namespace App\Http\Controllers;

use App\Http\Requests\Support\StoreSupportSenderRuleRequest;
use App\Http\Requests\Support\UpdateSupportSenderRuleRequest;
use App\Models\SupportSenderRule;
use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupportSenderRuleController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage', SupportTicket::class);

        $rules = SupportSenderRule::query()->with('type')->orderBy('pattern')->get();
        $types = SupportTicketType::query()->orderBy('sort')->orderBy('name')->get();

        return view('support.rules.index', compact('rules', 'types'));
    }

    public function store(StoreSupportSenderRuleRequest $request): RedirectResponse
    {
        SupportSenderRule::create($request->validated());

        return redirect()->route('support.rules.index')->with('success', 'Regola creata.');
    }

    public function update(UpdateSupportSenderRuleRequest $request, SupportSenderRule $rule): RedirectResponse
    {
        $rule->update($request->validated());

        return redirect()->route('support.rules.index')->with('success', 'Regola aggiornata.');
    }

    public function destroy(SupportSenderRule $rule): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $rule->delete();

        return redirect()->route('support.rules.index')->with('success', 'Regola eliminata.');
    }
}
