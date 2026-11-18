<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\SupportKnowledgeEntry;
use App\Models\SupportSavedReply;
use App\Models\SupportSenderRule;
use App\Models\SupportTicket;
use App\Models\SupportTicketType;
use App\Models\User;
use App\Ticketing\Enums\SavedReplyScope;
use App\Ticketing\Support\TicketingSettings;
use Database\Seeders\SupportTicketTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->amministratore()->create();
    }

    private function agent(): User
    {
        return User::factory()->ticketAgent()->create();
    }

    private function globalReply(array $attributes = []): SupportSavedReply
    {
        return SupportSavedReply::create($attributes + [
            'title' => 'Risposta globale',
            'body' => 'Ciao {cliente_nome}',
            'scope' => SavedReplyScope::Global,
            'active' => true,
            'sort' => 0,
        ]);
    }

    private function personalReply(User $owner, array $attributes = []): SupportSavedReply
    {
        return SupportSavedReply::create($attributes + [
            'title' => 'Risposta personale',
            'body' => 'Testo personale',
            'scope' => SavedReplyScope::Personal,
            'owner_user_id' => $owner->id,
            'active' => true,
            'sort' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function typePayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Garanzia',
            'slug' => 'garanzia',
            'description' => 'Usa questa tipologia per richieste di garanzia.',
            'color' => 'green',
            'assignable' => 1,
            'active' => 1,
            'sort' => 3,
        ];
    }

    public function test_admin_can_create_a_type(): void
    {
        $this->actingAs($this->admin())
            ->post(route('support.types.store'), $this->typePayload())
            ->assertRedirect(route('support.types.index'));

        $this->assertDatabaseHas('support_ticket_types', ['slug' => 'garanzia', 'name' => 'Garanzia', 'color' => 'green', 'is_system' => 0]);
    }

    public function test_type_slug_must_be_valid_and_unique(): void
    {
        SupportTicketType::factory()->create(['slug' => 'garanzia']);

        $this->actingAs($this->admin())
            ->post(route('support.types.store'), $this->typePayload())
            ->assertSessionHasErrors('slug');

        $this->post(route('support.types.store'), $this->typePayload(['slug' => 'Non Valido!']))
            ->assertSessionHasErrors('slug');
    }

    public function test_admin_can_update_a_type(): void
    {
        $type = SupportTicketType::factory()->create(['slug' => 'vecchio']);

        $this->actingAs($this->admin())
            ->put(route('support.types.update', $type), $this->typePayload(['slug' => 'nuovo', 'active' => 0]))
            ->assertRedirect(route('support.types.index'));

        $type->refresh();
        $this->assertSame('nuovo', $type->slug);
        $this->assertFalse($type->active);
    }

    public function test_system_type_keeps_its_slug_but_can_be_renamed_and_deactivated(): void
    {
        $this->seed(SupportTicketTypeSeeder::class);
        $type = SupportTicketType::where('slug', 'spam')->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('support.types.update', $type), $this->typePayload(['slug' => 'altro_slug', 'name' => 'Posta indesiderata', 'active' => 0]))
            ->assertRedirect(route('support.types.index'));

        $type->refresh();
        $this->assertSame('spam', $type->slug);
        $this->assertSame('Posta indesiderata', $type->name);
        $this->assertFalse($type->active);
    }

    public function test_system_type_cannot_be_deleted(): void
    {
        $this->seed(SupportTicketTypeSeeder::class);
        $type = SupportTicketType::where('slug', 'spam')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('support.types.destroy', $type))
            ->assertRedirect(route('support.types.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($type);
    }

    public function test_type_with_tickets_cannot_be_deleted_or_change_slug(): void
    {
        $type = SupportTicketType::factory()->create(['slug' => 'usata']);
        SupportTicket::factory()->ofType($type)->create(['public_id' => 'T-9001']);

        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('support.types.destroy', $type))->assertSessionHas('error');
        $this->assertModelExists($type);

        $this->put(route('support.types.update', $type), $this->typePayload(['slug' => 'cambiata']));
        $this->assertSame('usata', $type->fresh()->slug);
    }

    public function test_unused_custom_type_can_be_deleted(): void
    {
        $type = SupportTicketType::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('support.types.destroy', $type))
            ->assertSessionHas('success');

        $this->assertModelMissing($type);
    }

    public function test_type_pages_render_for_admin(): void
    {
        $this->seed(SupportTicketTypeSeeder::class);
        $type = SupportTicketType::first();

        $this->actingAs($this->admin());

        $this->get(route('support.types.index'))->assertOk()->assertSee($type->name);
        $this->get(route('support.types.create'))->assertOk();
        $this->get(route('support.types.edit', $type))->assertOk();
    }

    public function test_admin_can_create_a_sender_rule_normalized_to_lowercase(): void
    {
        $type = SupportTicketType::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('support.rules.store'), ['pattern' => '  @Esempio.IT ', 'support_ticket_type_id' => $type->id, 'active' => 1])
            ->assertRedirect(route('support.rules.index'));

        $this->assertDatabaseHas('support_sender_rules', ['pattern' => '@esempio.it', 'support_ticket_type_id' => $type->id]);

        $this->get(route('support.rules.index'))->assertOk()->assertSee('@esempio.it');
    }

    public function test_invalid_or_duplicate_sender_rule_pattern_is_rejected(): void
    {
        $type = SupportTicketType::factory()->create();
        SupportSenderRule::create(['pattern' => 'mario@x.it', 'support_ticket_type_id' => $type->id]);

        $this->actingAs($this->admin());

        foreach (['mario', 'esempio.it', '@esempio', 'a b@x.it', 'mario@x.it'] as $pattern) {
            $this->post(route('support.rules.store'), ['pattern' => $pattern, 'support_ticket_type_id' => $type->id])
                ->assertSessionHasErrors('pattern');
        }

        $this->assertSame(1, SupportSenderRule::count());
    }

    public function test_admin_can_update_and_delete_a_sender_rule(): void
    {
        $first = SupportTicketType::factory()->create();
        $second = SupportTicketType::factory()->create();
        $rule = SupportSenderRule::create(['pattern' => 'mario@x.it', 'support_ticket_type_id' => $first->id, 'active' => true]);

        $this->actingAs($this->admin())
            ->put(route('support.rules.update', $rule), ['support_ticket_type_id' => $second->id, 'note' => 'Fornitore', 'active' => 0])
            ->assertRedirect(route('support.rules.index'));

        $rule->refresh();
        $this->assertSame($second->id, $rule->support_ticket_type_id);
        $this->assertSame('Fornitore', $rule->note);
        $this->assertFalse($rule->active);

        $this->delete(route('support.rules.destroy', $rule))->assertRedirect(route('support.rules.index'));
        $this->assertModelMissing($rule);
    }

    public function test_admin_can_manage_knowledge_entries(): void
    {
        $type = SupportTicketType::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('support.knowledge.store'), [
                'title' => 'Orari',
                'content' => 'Siamo aperti dal lunedì al venerdì.',
                'ticket_type_ids' => [$type->id],
                'active' => 1,
                'sort' => 1,
            ])
            ->assertRedirect(route('support.knowledge.index'));

        $entry = SupportKnowledgeEntry::firstOrFail();
        $this->assertSame([$type->id], $entry->ticket_type_ids);

        $this->put(route('support.knowledge.update', $entry), ['title' => 'Orari', 'content' => 'Nuovo testo', 'active' => 0, 'sort' => 2])
            ->assertRedirect(route('support.knowledge.index'));

        $entry->refresh();
        $this->assertNull($entry->ticket_type_ids);
        $this->assertFalse($entry->active);

        $this->get(route('support.knowledge.index'))->assertOk()->assertSee('Orari')->assertSee('Disattiva');
        $this->get(route('support.knowledge.create'))->assertOk();
        $this->get(route('support.knowledge.edit', $entry))->assertOk();

        $this->delete(route('support.knowledge.destroy', $entry))->assertRedirect(route('support.knowledge.index'));
        $this->assertModelMissing($entry);
    }

    public function test_enabled_agent_gets_403_on_types_rules_and_knowledge(): void
    {
        $type = SupportTicketType::factory()->create();
        $rule = SupportSenderRule::create(['pattern' => 'mario@x.it', 'support_ticket_type_id' => $type->id]);
        $entry = SupportKnowledgeEntry::create(['title' => 'T', 'content' => 'C']);

        $this->actingAs($this->agent());

        $this->get(route('support.types.index'))->assertForbidden();
        $this->get(route('support.types.create'))->assertForbidden();
        $this->post(route('support.types.store'), $this->typePayload())->assertForbidden();
        $this->get(route('support.types.edit', $type))->assertForbidden();
        $this->put(route('support.types.update', $type), $this->typePayload())->assertForbidden();
        $this->delete(route('support.types.destroy', $type))->assertForbidden();

        $this->get(route('support.rules.index'))->assertForbidden();
        $this->post(route('support.rules.store'), ['pattern' => 'a@b.it', 'support_ticket_type_id' => $type->id])->assertForbidden();
        $this->put(route('support.rules.update', $rule), ['support_ticket_type_id' => $type->id])->assertForbidden();
        $this->delete(route('support.rules.destroy', $rule))->assertForbidden();

        $this->get(route('support.knowledge.index'))->assertForbidden();
        $this->get(route('support.knowledge.create'))->assertForbidden();
        $this->post(route('support.knowledge.store'), ['title' => 'x', 'content' => 'y'])->assertForbidden();
        $this->get(route('support.knowledge.edit', $entry))->assertForbidden();
        $this->put(route('support.knowledge.update', $entry), ['title' => 'x', 'content' => 'y'])->assertForbidden();
        $this->delete(route('support.knowledge.destroy', $entry))->assertForbidden();
    }

    public function test_user_not_enabled_gets_403_on_saved_replies(): void
    {
        $reply = $this->globalReply();

        $this->actingAs(User::factory()->create());

        $this->get(route('support.saved-replies.index'))->assertForbidden();
        $this->get(route('support.saved-replies.create'))->assertForbidden();
        $this->post(route('support.saved-replies.store'), ['title' => 'x', 'body' => 'y', 'scope' => 'personal'])->assertForbidden();
        $this->get(route('support.saved-replies.edit', $reply))->assertForbidden();
        $this->post(route('support.saved-replies.duplicate', $reply))->assertForbidden();
        $this->post(route('support.saved-replies.from-text'), ['title' => 'x', 'body' => 'y'])->assertForbidden();
    }

    public function test_agent_sees_global_and_own_personal_replies_only(): void
    {
        $agent = $this->agent();
        $other = $this->agent();
        $this->globalReply(['title' => 'Globale visibile']);
        $this->personalReply($agent, ['title' => 'Mia personale']);
        $this->personalReply($other, ['title' => 'Personale altrui']);

        $this->actingAs($agent)
            ->get(route('support.saved-replies.index'))
            ->assertOk()
            ->assertSee('Globale visibile')
            ->assertSee('Mia personale')
            ->assertDontSee('Personale altrui');
    }

    public function test_admin_sees_all_replies_with_owner_name(): void
    {
        $agent = User::factory()->ticketAgent()->create(['first_name' => 'Luigi', 'last_name' => 'Verdi']);
        $this->globalReply(['title' => 'Globale visibile']);
        $this->personalReply($agent, ['title' => 'Personale di Luigi']);

        $this->actingAs($this->admin())
            ->get(route('support.saved-replies.index'))
            ->assertOk()
            ->assertSee('Globale visibile')
            ->assertSee('Personale di Luigi')
            ->assertSee('Luigi Verdi');
    }

    public function test_replies_can_be_filtered_by_search_and_scope(): void
    {
        $agent = $this->agent();
        $this->globalReply(['title' => 'Spedizione ritardo']);
        $this->personalReply($agent, ['title' => 'Saluti finali']);

        $this->actingAs($agent);

        $this->get(route('support.saved-replies.index', ['q' => 'ritardo']))
            ->assertSee('Spedizione ritardo')->assertDontSee('Saluti finali');
        $this->get(route('support.saved-replies.index', ['scope' => 'personal']))
            ->assertSee('Saluti finali')->assertDontSee('Spedizione ritardo');
    }

    public function test_agent_can_create_update_and_delete_own_personal_reply(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)
            ->post(route('support.saved-replies.store'), ['title' => 'Mia', 'body' => 'Ciao {cliente_nome}', 'scope' => 'personal', 'active' => 1])
            ->assertRedirect(route('support.saved-replies.index'));

        $reply = SupportSavedReply::firstOrFail();
        $this->assertSame(SavedReplyScope::Personal, $reply->scope);
        $this->assertSame($agent->id, $reply->owner_user_id);
        $this->assertSame($agent->id, $reply->created_by);

        $this->get(route('support.saved-replies.edit', $reply))->assertOk()->assertSee('Anteprima')->assertSee('Ciao Mario');

        $this->put(route('support.saved-replies.update', $reply), ['title' => 'Mia v2', 'body' => 'Nuovo', 'active' => 1])
            ->assertRedirect(route('support.saved-replies.index'));
        $this->assertSame('Mia v2', $reply->fresh()->title);

        $this->delete(route('support.saved-replies.destroy', $reply))->assertRedirect(route('support.saved-replies.index'));
        $this->assertModelMissing($reply);
    }

    public function test_agent_cannot_create_global_reply_even_with_direct_post(): void
    {
        $this->actingAs($this->agent())
            ->post(route('support.saved-replies.store'), ['title' => 'Globale', 'body' => 'x', 'scope' => 'global'])
            ->assertForbidden();

        $this->assertSame(0, SupportSavedReply::count());
    }

    public function test_agent_cannot_modify_global_or_foreign_personal_replies(): void
    {
        $agent = $this->agent();
        $global = $this->globalReply();
        $foreign = $this->personalReply($this->agent());

        $this->actingAs($agent);

        foreach ([$global, $foreign] as $reply) {
            $this->get(route('support.saved-replies.edit', $reply))->assertForbidden();
            $this->put(route('support.saved-replies.update', $reply), ['title' => 'Hack', 'body' => 'x'])->assertForbidden();
            $this->delete(route('support.saved-replies.destroy', $reply))->assertForbidden();
        }

        $this->post(route('support.saved-replies.promote', $foreign))->assertForbidden();
        $this->post(route('support.saved-replies.duplicate', $foreign))->assertForbidden();
        $this->assertNotSame('Hack', $global->fresh()->title);
    }

    public function test_admin_can_create_and_edit_global_replies_and_promote_personal_ones(): void
    {
        $admin = $this->admin();
        $agent = $this->agent();
        $personal = $this->personalReply($agent);

        $this->actingAs($admin)
            ->post(route('support.saved-replies.store'), ['title' => 'Per tutti', 'body' => 'x', 'scope' => 'global', 'active' => 1])
            ->assertRedirect(route('support.saved-replies.index'));

        $global = SupportSavedReply::where('title', 'Per tutti')->firstOrFail();
        $this->assertSame(SavedReplyScope::Global, $global->scope);
        $this->assertNull($global->owner_user_id);

        $this->put(route('support.saved-replies.update', $global), ['title' => 'Per tutti v2', 'body' => 'y', 'active' => 1])
            ->assertRedirect();
        $this->assertSame('Per tutti v2', $global->fresh()->title);

        $this->post(route('support.saved-replies.promote', $personal))->assertRedirect(route('support.saved-replies.index'));
        $personal->refresh();
        $this->assertSame(SavedReplyScope::Global, $personal->scope);
        $this->assertNull($personal->owner_user_id);
    }

    public function test_duplicate_creates_personal_copy_for_agent_and_global_copy_for_admin(): void
    {
        $global = $this->globalReply(['title' => 'Originale']);
        $agent = $this->agent();

        $this->actingAs($agent)->post(route('support.saved-replies.duplicate', $global))->assertRedirect(route('support.saved-replies.index'));

        $copy = SupportSavedReply::where('title', 'Originale (copia)')->firstOrFail();
        $this->assertSame(SavedReplyScope::Personal, $copy->scope);
        $this->assertSame($agent->id, $copy->owner_user_id);
        $this->assertSame(0, $copy->usage_count);

        $this->actingAs($this->admin())->post(route('support.saved-replies.duplicate', $global));

        $this->assertSame(1, SupportSavedReply::where('title', 'Originale (copia)')->where('scope', 'global')->count());
    }

    public function test_personal_replies_disabled_by_setting_are_hidden_and_forbidden(): void
    {
        $admin = $this->admin();
        $agent = $this->agent();
        app(TicketingSettings::class)->set('personal_saved_replies_enabled', false, $admin);

        $this->globalReply(['title' => 'Globale attiva']);
        $personal = $this->personalReply($agent, ['title' => 'Personale nascosta']);

        $this->actingAs($agent);

        $this->get(route('support.saved-replies.index'))
            ->assertOk()
            ->assertSee('Globale attiva')
            ->assertDontSee('Personale nascosta');
        $this->get(route('support.saved-replies.create'))->assertForbidden();
        $this->post(route('support.saved-replies.store'), ['title' => 'x', 'body' => 'y', 'scope' => 'personal'])->assertForbidden();
        $this->get(route('support.saved-replies.edit', $personal))->assertForbidden();
        $this->post(route('support.saved-replies.from-text'), ['title' => 'x', 'body' => 'y'])->assertForbidden();

        $this->actingAs($admin)
            ->get(route('support.saved-replies.index'))
            ->assertOk()
            ->assertDontSee('Personale nascosta');
    }

    public function test_from_text_abstracts_ticket_values_and_creates_personal_reply(): void
    {
        $agent = $this->agent();
        $client = Client::create(['first_name' => 'Mario', 'last_name' => 'Rossi', 'email' => 'mario@example.com']);
        Project::create(['name' => 'Sito vetrina Rossi', 'project_type' => 'website', 'client_id' => $client->id, 'start_date' => now(), 'status' => 'in_progress']);
        $ticket = SupportTicket::factory()->assignedTo($agent)->create(['public_id' => 'T-9002', 'customer_name' => 'Mario Rossi', 'client_id' => $client->id]);

        $this->actingAs($agent)
            ->from(route('support.saved-replies.index'))
            ->post(route('support.saved-replies.from-text'), [
                'title' => 'Da testo',
                'body' => 'Ciao Mario, il ticket T-9002 su Sito vetrina Rossi è chiuso.',
                'support_ticket_id' => $ticket->id,
            ])
            ->assertRedirect(route('support.saved-replies.index'))
            ->assertSessionHas('success');

        $reply = SupportSavedReply::firstOrFail();
        $this->assertSame('Ciao {cliente_nome}, il ticket {numero_ticket} su {progetto} è chiuso.', $reply->body);
        $this->assertSame(SavedReplyScope::Personal, $reply->scope);
        $this->assertSame($agent->id, $reply->owner_user_id);
    }

    public function test_from_text_requires_access_to_the_ticket(): void
    {
        $ticket = SupportTicket::factory()->assignedTo($this->agent())->create(['public_id' => 'T-9003']);

        $this->actingAs($this->agent())
            ->post(route('support.saved-replies.from-text'), ['title' => 'x', 'body' => 'y', 'support_ticket_id' => $ticket->id])
            ->assertForbidden();

        $this->assertSame(0, SupportSavedReply::count());
    }

    public function test_from_text_global_scope_is_ignored_for_agents_and_honoured_for_admins(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)->post(route('support.saved-replies.from-text'), ['title' => 'A', 'body' => 'x', 'scope' => 'global']);
        $this->assertSame(SavedReplyScope::Personal, SupportSavedReply::where('title', 'A')->firstOrFail()->scope);

        $this->actingAs($this->admin())->post(route('support.saved-replies.from-text'), ['title' => 'B', 'body' => 'x', 'scope' => 'global']);
        $this->assertSame(SavedReplyScope::Global, SupportSavedReply::where('title', 'B')->firstOrFail()->scope);
    }
}
