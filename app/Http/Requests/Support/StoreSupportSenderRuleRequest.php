<?php

namespace App\Http\Requests\Support;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupportSenderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', SupportTicket::class);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('pattern'))) {
            $this->merge(['pattern' => mb_strtolower(trim($this->input('pattern')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pattern' => [
                'required',
                'string',
                'max:255',
                'regex:/^(@[a-z0-9.-]+\.[a-z]{2,}|[^@\s]+@[a-z0-9.-]+\.[a-z]{2,})$/',
                'unique:support_sender_rules,pattern',
            ],
            'support_ticket_type_id' => ['required', 'integer', 'exists:support_ticket_types,id'],
            'note' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['pattern' => 'indirizzo o dominio', 'support_ticket_type_id' => 'tipologia', 'note' => 'note'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['pattern.regex' => 'Usa un indirizzo completo (mario@esempio.it) oppure un dominio (@esempio.it).'];
    }
}
