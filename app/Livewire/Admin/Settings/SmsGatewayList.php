<?php

namespace App\Livewire\Admin\Settings;

use App\Models\SmsGateway;
use Livewire\Component;

class SmsGatewayList extends Component
{
    public bool $drawerOpen = false;
    public ?int $editingId = null;
    public ?int $checkingBalanceId = null;

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

    // BulkSMS Dhaka
    public string $fApiToken = '';
    public string $fSenderId = '';

    // Alpha SMS
    public string $fAlphaApiKey = '';
    public string $fAlphaType = 'text';
    public string $fAlphaApiUrlSend = '';
    public string $fAlphaApiUrlBalance = '';

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
            'bulk_sms_dhaka' => [
                $this->fApiToken = $creds['api_token'] ?? '',
                $this->fSenderId = $creds['sender_id'] ?? '',
            ],
            'alpha_sms' => [
                $this->fAlphaApiKey = $creds['api_key'] ?? '',
                $this->fAlphaType = $creds['type'] ?? 'text',
                $this->fAlphaApiUrlSend = $creds['api_url_send'] ?? 'https://api.sms.net.bd/sendsms',
                $this->fAlphaApiUrlBalance = $creds['api_url_balance'] ?? 'https://api.sms.net.bd/user/balance/',
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
            'bulk_sms_dhaka' => [
                'api_token' => $this->fApiToken,
                'sender_id' => $this->fSenderId,
            ],
            'alpha_sms' => [
                'api_key'         => $this->fAlphaApiKey,
                'type'            => $this->fAlphaType,
                'api_url_send'    => $this->fAlphaApiUrlSend,
                'api_url_balance' => $this->fAlphaApiUrlBalance,
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

    public function checkBalance(int $id): void
    {
        try {
            $gateway = SmsGateway::find($id);
            if (!$gateway) {
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => 'Gateway not found.',
                ]);
                return;
            }

            if ($gateway->provider !== 'alpha_sms') {
                $this->dispatch('toast', [
                    'type' => 'warning',
                    'message' => 'Balance check available only for Alpha SMS.',
                ]);
                return;
            }

            if (!isset($gateway->credentials['api_key']) || empty($gateway->credentials['api_key'])) {
                $this->dispatch('toast', [
                    'type' => 'warning',
                    'message' => 'API key not configured. Please edit and save.',
                ]);
                return;
            }

            $this->checkingBalanceId = $id;

            $provider = new \App\Services\Sms\Providers\AlphaSmsProvider($gateway->credentials);
            $result = $provider->checkBalance();

            if ($result['success']) {
                $balance = number_format((float)($result['balance'] ?? 0), 2);
                $currency = $result['currency'] ?? 'TK';
                $this->dispatch('toast', [
                    'type' => 'success',
                    'message' => "💰 Balance: {$balance} {$currency}",
                ]);
            } else {
                $error = $result['error'] ?? 'Unable to check balance';
                $this->dispatch('toast', [
                    'type' => 'error',
                    'message' => $error,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Balance check error', [
                'gateway_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'Error checking balance. Please try again.',
            ]);
        } finally {
            $this->checkingBalanceId = null;
        }
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
        $this->fApiToken = '';
        $this->fSenderId = '';
        $this->fAlphaApiKey = '';
        $this->fAlphaType = 'text';
        $this->fAlphaApiUrlSend = '';
        $this->fAlphaApiUrlBalance = '';
    }

    public function render()
    {
        $gateways = SmsGateway::orderBy('created_at', 'desc')->get();

        return view('livewire.admin.settings.sms-gateway-list', compact('gateways'))
            ->layout('layouts.admin.admin');
    }
}
