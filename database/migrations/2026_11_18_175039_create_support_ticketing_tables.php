<?php

use Database\Seeders\SupportTicketTypeSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email ticketing (ported from Noli Gestionale) with several mailboxes: Gmail (X-GM extensions, OAuth)
 * or any IMAP/SMTP server.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('can_manage_tickets')->default(false);
            $table->boolean('ticket_available')->default(true);
            $table->timestamp('last_ticket_assigned_at')->nullable();
        });

        Schema::create('mailboxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address')->unique();
            $table->string('driver', 20); // gmail | imap
            $table->string('imap_host');
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->string('smtp_host');
            $table->unsignedSmallInteger('smtp_port')->default(465);
            $table->string('username');
            $table->text('password');
            $table->text('oauth_refresh_token')->nullable();
            $table->timestamp('oauth_connected_at')->nullable();
            $table->string('folder')->default('INBOX');
            $table->boolean('active')->default(true);
            $table->string('uid_validity')->nullable();
            $table->unsignedBigInteger('last_uid')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('disconnected_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->unsignedInteger('sent_today')->default(0);
            $table->date('sent_today_date')->nullable();
            $table->timestamps();
        });

        Schema::create('support_ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 20)->default('slate');
            $table->boolean('assignable')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('support_sender_rules', function (Blueprint $table) {
            $table->id();
            $table->string('pattern')->unique();
            $table->foreignId('support_ticket_type_id')->constrained('support_ticket_types')->cascadeOnDelete();
            $table->string('note')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 20)->unique();
            $table->foreignId('mailbox_id')->nullable()->constrained('mailboxes')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('status', 20)->default('open')->index();
            $table->string('customer_email')->index();
            $table->string('customer_name')->nullable();
            $table->foreignId('support_ticket_type_id')->nullable()->constrained('support_ticket_types')->nullOnDelete();
            $table->string('type_source', 20)->nullable();
            $table->decimal('ai_confidence', 4, 3)->nullable();
            $table->string('ai_summary', 255)->nullable();
            $table->string('language', 10)->nullable();
            $table->string('subject')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Gmail X-GM-THRID; null for plain IMAP mailboxes (threads are matched by headers and [T-…] token)
            $table->string('gmail_thread_id')->nullable()->index();
            $table->foreignId('related_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $table->foreignId('previous_ticket_id')->nullable()->constrained('support_tickets')->nullOnDelete();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('gmail_trashed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('close_reason', 20)->nullable();
            $table->unsignedSmallInteger('reopened_count')->default(0);
            $table->timestamp('assigned_at')->nullable();
            $table->text('reply_draft')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'assigned_user_id']);
            $table->index('created_at');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->string('direction', 5);
            $table->string('type', 10)->default('reply');
            $table->string('delivery_status', 10)->default('received');
            $table->text('send_error')->nullable();
            // Gmail X-GM-MSGID, or "imap-{mailbox}-{sha1}" for plain IMAP mailboxes: the dedup key of inbound mail
            $table->string('gmail_message_id')->nullable()->unique();
            $table->string('message_id_header', 320)->nullable()->index();
            $table->string('in_reply_to', 320)->nullable();
            $table->text('references')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('to_email')->nullable();
            $table->string('subject')->nullable();
            $table->longText('body_text')->nullable();
            $table->longText('body_html_sanitized')->nullable();
            $table->string('raw_path')->nullable();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('saved_reply_id')->nullable();
            $table->unsignedBigInteger('ai_draft_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['support_ticket_id', 'created_at']);
        });

        Schema::create('support_ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_message_id')->constrained('support_ticket_messages')->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('content_id')->nullable();
            $table->boolean('inline')->default(false);
            $table->string('disk', 30)->nullable();
            $table->string('path')->nullable();
            $table->boolean('skipped_too_large')->default(false);
            $table->timestamps();
        });

        Schema::create('support_ticket_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 20);
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('support_ticket_type_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('from_type_id')->nullable()->constrained('support_ticket_types')->nullOnDelete();
            $table->foreignId('to_type_id')->nullable()->constrained('support_ticket_types')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('support_saved_replies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->foreignId('support_ticket_type_id')->nullable()->constrained('support_ticket_types')->nullOnDelete();
            $table->string('scope', 10)->default('global');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('support_ai_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('based_on_message_id')->nullable()->constrained('support_ticket_messages')->nullOnDelete();
            $table->text('instruction')->nullable();
            $table->string('model');
            $table->string('prompt_version', 20);
            $table->longText('draft_text');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->boolean('used')->default(false);
            $table->boolean('pregenerated')->default(false);
            $table->foreignId('sent_message_id')->nullable()->constrained('support_ticket_messages')->nullOnDelete();
            $table->decimal('similarity', 4, 3)->nullable();
            $table->string('feedback', 4)->nullable();
            $table->timestamps();
        });

        Schema::create('support_knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->json('ticket_type_ids')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('ticketing_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('ticketing_setting_logs', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // the classifier and the sender rules need the system types from day one
        (new SupportTicketTypeSeeder)->run();
    }

    public function down(): void
    {
        foreach ([
            'ticketing_setting_logs', 'ticketing_settings', 'support_knowledge_entries', 'support_ai_drafts',
            'support_saved_replies', 'support_ticket_type_changes', 'support_ticket_assignments',
            'support_ticket_attachments', 'support_ticket_messages', 'support_tickets', 'support_sender_rules',
            'support_ticket_types', 'mailboxes',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropColumn(['is_active', 'can_manage_tickets', 'ticket_available', 'last_ticket_assigned_at']);
        });
    }
};
