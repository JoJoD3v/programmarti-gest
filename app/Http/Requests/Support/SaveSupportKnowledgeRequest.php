<?php

namespace App\Http\Requests\Support;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class SaveSupportKnowledgeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', SupportTicket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'ticket_type_ids' => ['nullable', 'array'],
            'ticket_type_ids.*' => ['integer', 'exists:support_ticket_types,id'],
            'active' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'titolo', 'content' => 'contenuto', 'ticket_type_ids' => 'tipologie', 'sort' => 'ordine'];
    }
}
