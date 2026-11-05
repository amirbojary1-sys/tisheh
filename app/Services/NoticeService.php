<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityNotice;

class NoticeService
{
    public function send(User $user, string $title, string $body, string $path): void
    {
        $user->notify(new ActivityNotice($title, $body, $path));
    }

    public function admins(string $title, string $body, string $path): void
    {
        User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->where('status', 'active')->each(fn ($u) => $this->send($u, $title, $body, $path));
    }
}
