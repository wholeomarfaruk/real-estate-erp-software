<?php

namespace App\Livewire\Admin\Settings;

use App\Models\SmsGateway;
use Livewire\Component;

class SmsGatewayList extends Component
{
    public bool $drawerOpen = false;
    public ?int $editingId = null;

    public string $fName = '';
    public string $fProvider = 'ssl_wireless';
    public bool $fIsActive = false;

    // SSL Wireless
    public string $fApiUrl = '';
    public string $fApiUser = '';
    public string $fApiPassword = '';
    public string $fSid = '';

    // Twilio
    public string $fAccountSid = '';
    public string $fAuthToken = '';
    public string $fFromNumber = '';

    // Vonage
    public string $fApiKey = '';
    public string $fApiSecret = '';
    public string $fFrom = '';

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->resetFormFields();
        $this->drawerOpen = true;
    }

    public function openEdit(int $id): void
    {
        $gateway = SmsGateway::find($id);
        if (!$gateway) return;

        $this->editingId = $id;
        $this->fName = $gateway->name;
        $this->fProvider = $gateway->provider;
        $this->fIsActive = $gateway->is_active;

        $creds = $gateway->credentials;
        match($gateway->provider) {
            'ssl_wireless' => [
                $this->fApiUrl = $creds['api_url'] ?? '',
                $this->fApiUser = $creds['api_user'] ?? '',
                $this->fApiPassword = $creds['api_password'] ?? '',
                $this->fSid = $creds['sid'] ?? '',
            ],
            'twilio' => [
                $this->fAccountSid = $creds['account_sid'] ?? '',
                $this->fAuthToken = $creds['auth_token'] ?? '',
                $this->fFromNumber = $creds['from_number'] ?? '',
            ],
            'vonage' => [
                $this->fApiKey = $creds['api_key'] ?? '',
                $this->fApiSecret = $creds['api_secret'] ?? '',
                $this->fFrom = $creds['from'] ?? '',
            ],
        };

        $this->drawerOpen = true;
    }

    public function save(): void
    {
        $this->validate([
            'fName'     => 'required|string|max:255',
            'fProvider' => 'required|string|max:100',
        ]);

        $credentials = match($this->fProvider) {
            'ssl_wireless' => [
                'api_url'      => $this->fApiUrl,
                'api_user'     => $this->fApiUser,
                'api_password' => $this->fApiPassword,
                'sid'          => $this->fSid,
            ],
            'twilio' => [
                'account_sid'  => $this->fAccountSid,
                'auth_token'   => $this->fAuthToken,
                'from_number'  => $this->fFromNumber,
            ],
            'vonage' => [
                'api_key'    => $this->fApiKey,
                'api_secret' => $this->fApiSecret,
                'from'       => $this->fFrom,
            ],
        };

        if ($this->fIsActive) {
            SmsGateway::where('is_active', true)->update(['is_active' => false]);
        }

        if ($this->editingId) {
            $gateway = SmsGateway::find($this->editingId);
            $gateway->update([
                'name'        => $this->fName,
                'provider'    => $this->fProvider,
                'credentials' => $credentials,
                'is_active'   => $this->fIsActive,
                'updated_by'  => auth()->id(),
            ]);
        } else {
            SmsGateway::create([
                'name'        => $this->fName,
                'provider'    => $this->fProvider,
                'credentials' => $credentials,
                'is_active'   => $this->fIsActive,
                'created_by'  => auth()->id(),
                'updated_by'  => auth()->id(),
            ]);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'SMS Gateway saved.']);
        $this->closeDrawer();
    }

    public function delete(int $id): void
    {
        SmsGateway::find($id)?->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'SMS Gateway deleted.']);
    }

    public function setActive(int $id): void
    {
        SmsGateway::where('is_active', true)->update(['is_active' => false]);
        SmsGateway::find($id)?->update(['is_active' => true, 'updated_by' => auth()->id()]);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'SMS Gateway activated.']);
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->resetFormFields();
    }

    private function resetFormFields(): void
    {
        $this->editingId = null;
        $this->fName = '';
        $this->fProvider = 'ssl_wireless';
        $this->fIsActive = false;
        $this->fApiUrl = '';
        $this->fApiUser = '';
        $this->fApiPassword = '';
        $this->fSid = '';
        $this->fAccountSid = '';
        $this->fAuthToken = '';
        $this->fFromNumber = '';
        $this->fApiKey = '';
        $this->fApiSecret = '';
        $this->fFrom = '';
    }

    public function render()
    {
        $gateways = SmsGateway::orderBy('created_at', 'desc')->get();

        return view('livewire.admin.settings.sms-gateway-list', compact('gateways'))
            ->layout('layouts.admin.admin');
    }
}
