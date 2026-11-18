<?php

namespace App\Http\Controllers;

use App\Http\Requests\Support\SaveSupportKnowledgeRequest;
use App\Models\SupportKnowledgeEntry;
use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SupportKnowledgeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage', SupportTicket::class);

        $entries = SupportKnowledgeEntry::query()->orderBy('sort')->orderBy('title')->get();
        $typeNames = SupportTicketType::query()->pluck('name', 'id');

        return view('support.knowledge.index', compact('entries', 'typeNames'));
    }

    public function create(): View
    {
        Gate::authorize('manage', SupportTicket::class);

        $entry = new SupportKnowledgeEntry(['active' => true, 'sort' => 0, 'ticket_type_ids' => []]);
        $types = $this->activeTypes();

        return view('support.knowledge.create', compact('entry', 'types'));
    }

    public function store(SaveSupportKnowledgeRequest $request): RedirectResponse
    {
        SupportKnowledgeEntry::create($this->payload($request->validated()));

        return redirect()->route('support.knowledge.index')->with('success', 'Informazione aggiunta.');
    }

    public function edit(SupportKnowledgeEntry $entry): View
    {
        Gate::authorize('manage', SupportTicket::class);

        $types = $this->activeTypes();

        return view('support.knowledge.edit', compact('entry', 'types'));
    }

    public function update(SaveSupportKnowledgeRequest $request, SupportKnowledgeEntry $entry): RedirectResponse
    {
        $entry->update($this->payload($request->validated()));

        return redirect()->route('support.knowledge.index')->with('success', 'Informazione aggiornata.');
    }

    public function destroy(SupportKnowledgeEntry $entry): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $entry->delete();

        return redirect()->route('support.knowledge.index')->with('success', 'Informazione eliminata.');
    }

    /**
     * @return Collection<int, SupportTicketType>
     */
    private function activeTypes(): Collection
    {
        return SupportTicketType::query()->active()->orderBy('sort')->orderBy('name')->get();
    }

    /**
     * Empty selection means "all types" and is stored as null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $ids = array_values(array_unique(array_map('intval', $data['ticket_type_ids'] ?? [])));

        $data['ticket_type_ids'] = $ids === [] ? null : $ids;
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }
}
