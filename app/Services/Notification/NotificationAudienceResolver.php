<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationAudienceResolver
{
    public function resolve(string $audienceType, array $audienceValue): Collection
    {
        return match ($audienceType) {
            'single'   => User::whereKey($audienceValue['user_id'] ?? null)->get(),
            'multiple' => User::whereIn('id', $audienceValue['user_ids'] ?? [])->get(),
            'role'     => User::role($audienceValue['role'] ?? '')->get(),
            'all'      => User::all(),
            default    => collect(),
        };
    }
}
