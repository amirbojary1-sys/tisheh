<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ActivityNotice extends Notification
{
    public function __construct(public string $title, public string $body, public string $path) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['title' => $this->title, 'body' => $this->body, 'path' => $this->path];
    }
}
