<?php

namespace App\Http\Requests\Support;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSupportSenderRuleRequest extends FormRequest
{
    protected $errorBag = 'rule_update';

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
        return ['support_ticket_type_id' => 'tipologia', 'note' => 'note'];
    }
}
