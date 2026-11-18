<?php

namespace App\Http\Controllers;

use App\Http\Requests\Support\SaveSupportTypeRequest;
use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupportTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage', SupportTicket::class);

        $types = SupportTicketType::query()->withCount('tickets')->orderBy('sort')->orderBy('name')->get();

        return view('support.types.index', compact('types'));
    }

    public function create(): View
    {
        Gate::authorize('manage', SupportTicket::class);

        $type = new SupportTicketType([
            'color' => 'slate',
            'assignable' => true,
            'active' => true,
            'sort' => 0,
        ]);
        $slugLocked = false;

        return view('support.types.create', compact('type', 'slugLocked'));
    }

    public function store(SaveSupportTypeRequest $request): RedirectResponse
    {
        SupportTicketType::create($this->payload($request->validated()));

        return redirect()->route('support.types.index')->with('success', 'Tipologia creata.');
    }

    public function edit(SupportTicketType $type): View
    {
        Gate::authorize('manage', SupportTicket::class);

        $slugLocked = $this->isSlugLocked($type);

        return view('support.types.edit', compact('type', 'slugLocked'));
    }

    public function update(SaveSupportTypeRequest $request, SupportTicketType $type): RedirectResponse
    {
        $data = $this->payload($request->validated());

        if ($this->isSlugLocked($type)) {
            unset($data['slug']);
        }

        $type->update($data);

        return redirect()->route('support.types.index')->with('success', 'Tipologia aggiornata.');
    }

    public function destroy(SupportTicketType $type): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        if ($type->is_system) {
            return redirect()->route('support.types.index')
                ->with('error', 'Le tipologie di sistema non si possono eliminare: puoi rinominarle o disattivarle.');
        }

        if ($type->tickets()->exists()) {
            return redirect()->route('support.types.index')
                ->with('error', 'Esistono ticket con questa tipologia: disattivala invece di eliminarla.');
        }

        $type->delete();

        return redirect()->route('support.types.index')->with('success', 'Tipologia eliminata.');
    }

    private function isSlugLocked(SupportTicketType $type): bool
    {
        return $type->is_system || $type->tickets()->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }
}
