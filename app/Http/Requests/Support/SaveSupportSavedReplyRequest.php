<?php

namespace App\Http\Requests\Support;

use App\Models\SupportTicket;
use App\Ticketing\Enums\SavedReplyScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSupportSavedReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', SupportTicket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'support_ticket_type_id' => ['nullable', 'integer', 'exists:support_ticket_types,id'],
            'scope' => ['nullable', Rule::enum(SavedReplyScope::class)],
            'active' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['title' => 'titolo', 'body' => 'testo', 'support_ticket_type_id' => 'tipologia', 'scope' => 'ambito', 'sort' => 'ordine'];
    }
}
