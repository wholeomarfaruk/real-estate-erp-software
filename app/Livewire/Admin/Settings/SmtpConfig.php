<?php

namespace App\Livewire\Admin\Settings;

use App\Models\SmtpConfiguration;
use Livewire\Component;
use Illuminate\Support\Facades\Crypt;

class SmtpConfig extends Component
{
    public string $fHost        = '';
    public int    $fPort        = 587;
    public string $fEncryption  = 'tls';
    public string $fUsername    = '';
    public string $fPassword    = '';
    public string $fFromAddress = '';
    public string $fFromName    = '';

    public bool $isTesting = false;
    public bool $isNew     = true;

    public function mount(): void
    {
        $config = SmtpConfiguration::first();

        if ($config) {
            $this->isNew        = false;
            $this->fHost        = $config->host;
            $this->fPort        = $config->port;
            $this->fEncryption  = $config->encryption;
            $this->fUsername    = $config->username;
            $this->fFromAddress = $config->from_address;
            $this->fFromName    = $config->from_name;
            // password intentionally not pre-filled
        }
    }

    public function save(): void
    {
        $rules = [
            'fHost'        => 'required|string|max:255',
            'fPort'        => 'required|integer|min:1|max:65535',
            'fEncryption'  => 'required|in:tls,ssl,none',
            'fUsername'    => 'required|string|max:255',
            'fFromAddress' => 'required|email|max:255',
            'fFromName'    => 'required|string|max:255',
        ];

        if ($this->isNew) {
            $rules['fPassword'] = 'required|string|min:1';
        }

        $this->validate($rules);

        $data = [
            'host'         => $this->fHost,
            'port'         => $this->fPort,
            'encryption'   => $this->fEncryption,
            'username'     => $this->fUsername,
            'from_address' => $this->fFromAddress,
            'from_name'    => $this->fFromName,
            'updated_by'   => auth()->id(),
        ];

        if ($this->fPassword !== '') {
            $data['password'] = Crypt::encryptString($this->fPassword);
        }

        $existing = SmtpConfiguration::first();

        if ($existing) {
            $existing->update($data);
        } else {
            $data['created_by'] = auth()->id();
            SmtpConfiguration::create($data);
            $this->isNew = false;
        }

        $this->fPassword = '';
        $this->dispatch('toast', ['type' => 'success', 'message' => 'SMTP configuration saved.']);
    }

    public function testConnection(): void
    {
        $this->isTesting = true;

        try {
            $config = SmtpConfiguration::first();

            if (! $config) {
                $this->dispatch('toast', ['type' => 'warning', 'message' => 'Save your configuration before testing.']);
                return;
            }

            $password = Crypt::decryptString($config->password);

            $transport = new \Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport(
                $config->host,
                $config->port,
                $config->encryption === 'ssl',
            );
            $transport->setUsername($config->username);
            $transport->setPassword($password);
            $transport->start();
            $transport->stop();

            $this->dispatch('toast', ['type' => 'success', 'message' => '✓ Connection successful! SMTP server is reachable.']);
        } catch (\Exception $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Connection failed: ' . $e->getMessage()]);
        } finally {
            $this->isTesting = false;
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.smtp-config')
            ->layout('layouts.admin.admin');
    }
}
