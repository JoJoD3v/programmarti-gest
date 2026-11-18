<?php

namespace App\Ticketing\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Database notification shown in the app: a short message and the page it links to.
 */
class TicketingNotification extends Notification
{
    public function __construct(
        public string $kind,
        public string $message,
        public ?string $url = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'message' => $this->message, 'url' => $this->url];
    }
}
