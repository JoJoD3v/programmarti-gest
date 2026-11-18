<?php

namespace App\Http\Requests\Support;

use App\Models\SupportTicket;
use App\Rules\NoUnresolvedPlaceholders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A ticket opened from the gestionale: the first message is an email sent to the customer.
 */
class StoreTicketRequest extends FormRequest
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
            'mailbox_id' => ['required', 'integer', Rule::exists('mailboxes', 'id')->where('active', true)],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'support_ticket_type_id' => ['nullable', 'integer', Rule::exists('support_ticket_types', 'id')->where('active', true)],
            'body' => ['required', 'string', 'max:20000', new NoUnresolvedPlaceholders],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:'.(int) config('ticketing.mailbox.max_attachment_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'mailbox_id' => 'casella di invio',
            'customer_email' => 'email del cliente',
            'customer_name' => 'nome del cliente',
            'subject' => 'oggetto',
            'support_ticket_type_id' => 'tipologia',
            'body' => 'messaggio',
            'attachments.*' => 'allegato',
        ];
    }
}
