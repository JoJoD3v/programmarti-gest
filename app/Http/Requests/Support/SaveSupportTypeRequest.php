<?php

namespace App\Http\Requests\Support;

use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSupportTypeRequest extends FormRequest
{
    public const COLORS = ['slate', 'red', 'amber', 'green', 'blue', 'indigo', 'purple', 'pink'];

    public function authorize(): bool
    {
        return $this->user()->can('manage', SupportTicket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var SupportTicketType|null $type */
        $type = $this->route('type');
        $slugLocked = $type !== null && ($type->is_system || $type->tickets()->exists());

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                $slugLocked ? 'nullable' : 'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('support_ticket_types', 'slug')->ignore($type?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['required', Rule::in(self::COLORS)],
            'assignable' => ['boolean'],
            'active' => ['boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'slug' => 'codice',
            'description' => 'descrizione',
            'color' => 'colore',
            'sort' => 'ordine',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['slug.regex' => 'Il codice può contenere solo lettere minuscole, numeri e underscore.'];
    }
}
