<?php

namespace App\Http\Requests\Support;

use App\Rules\NoUnresolvedPlaceholders;
use App\Ticketing\Enums\SavedReplyScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reply', $this->route('supportTicket'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000', new NoUnresolvedPlaceholders],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:'.(int) config('ticketing.mailbox.max_attachment_kb')],
            'saved_reply_id' => ['nullable', 'integer', Rule::exists('support_saved_replies', 'id')
                ->where(fn ($query) => $query->where('scope', '!=', SavedReplyScope::Personal->value)->orWhere('owner_user_id', $this->user()->id))],
            'ai_draft_id' => ['nullable', 'integer', Rule::exists('support_ai_drafts', 'id')->where('support_ticket_id', $this->route('supportTicket')->id)],
            'close' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'testo della risposta', 'attachments.*' => 'allegato'];
    }
}
