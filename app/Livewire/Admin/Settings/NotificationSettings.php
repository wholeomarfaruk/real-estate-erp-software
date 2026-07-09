<?php

namespace App\Livewire\Admin\Settings;

use App\Enums\Notification\NotificationGroup;
use App\Enums\Notification\NotificationType;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationSettings extends Component
{
    public array $enabledTypes = [];

    public bool $webPushEnabled = true;

    public function mount(): void
    {
        $settings = NotificationSetting::current();

        $this->enabledTypes = $settings->enabled_types;
        $this->webPushEnabled = $settings->web_push_enabled;
    }

    public function save(): void
    {
        $this->validate([
            'enabledTypes'   => 'array',
            'enabledTypes.*' => 'string|in:' . implode(',', array_column(NotificationType::cases(), 'value')),
            'webPushEnabled' => 'boolean',
        ]);

        NotificationSetting::current()->update([
            'enabled_types'    => $this->enabledTypes,
            'web_push_enabled' => $this->webPushEnabled,
            'updated_by'       => Auth::id(),
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Notification settings saved.']);
    }

    public function render()
    {
        $groupedTypes = collect(NotificationType::cases())->groupBy(
            fn (NotificationType $type) => $type->reportGroup()->value
        );

        $groups = NotificationGroup::cases();

        return view('livewire.admin.settings.notification-settings', compact('groupedTypes', 'groups'))
            ->layout('layouts.admin.admin');
    }
}
