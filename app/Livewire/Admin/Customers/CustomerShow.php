<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use App\Models\File;
use App\Models\User;
use App\Services\Client\ClientPasswordResetService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;
use RuntimeException;

class CustomerShow extends Component
{
    public Customer $customer;

    public function mount(Customer $customer): void
    {
        abort_unless(auth()->user()?->can('customer.view'), 403);

        $this->customer = $customer->load([
            'createdByUser',
            'updatedByUser',
            'user',
            'propertySales.propertyUnit.property',
            'propertySales.propertyUnit.floor',
        ]);
    }

    public function createLoginAccessEmail(): void
    {
        $this->createLoginAccess('email');
    }

    public function createLoginAccessSms(): void
    {
        $this->createLoginAccess('sms');
    }

    public function sendPasswordResetEmail(): void
    {
        $this->sendPasswordReset('email');
    }

    public function sendPasswordResetSms(): void
    {
        $this->sendPasswordReset('sms');
    }

    public function resetPasswordAndSendEmail(): void
    {
        $this->resetPasswordAndSend('email');
    }

    public function resetPasswordAndSendSms(): void
    {
        $this->resetPasswordAndSend('sms');
    }

    private function createLoginAccess(string $channel): void
    {
        abort_unless(auth()->user()?->can('customer.edit'), 403);

        if ($this->customer->user_id) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'A login already exists for this customer.']);

            return;
        }

        if (blank($this->channelDestination($channel))) {
            $this->dispatch('toast', ['type' => 'error', 'message' => "This customer has no {$channel} on file."]);

            return;
        }

        try {
            DB::transaction(function () use ($channel) {
                $user = $this->createUserForCustomer();

                app(ClientPasswordResetService::class)->send($user, $channel);
            });
        } catch (UniqueConstraintViolationException) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'An account with this email already exists.']);

            return;
        } catch (RuntimeException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->customer->refresh()->load('user');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Login created and password reset code sent.']);
    }

    private function sendPasswordReset(string $channel): void
    {
        abort_unless(auth()->user()?->can('customer.edit'), 403);

        $user = $this->customer->user;

        if (! $user) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'This customer has no login access yet.']);

            return;
        }

        if (blank($this->channelDestination($channel))) {
            $this->dispatch('toast', ['type' => 'error', 'message' => "This customer has no {$channel} on file."]);

            return;
        }

        try {
            app(ClientPasswordResetService::class)->send($user, $channel);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Password reset code sent.']);
    }

    /**
     * Generates a brand-new password and emails/texts the credentials
     * directly. Creates the login first if one doesn't exist yet.
     */
    private function resetPasswordAndSend(string $channel): void
    {
        abort_unless(auth()->user()?->can('customer.edit'), 403);

        if (blank($this->channelDestination($channel))) {
            $this->dispatch('toast', ['type' => 'error', 'message' => "This customer has no {$channel} on file."]);

            return;
        }

        try {
            DB::transaction(function () use ($channel) {
                $user = $this->customer->user_id ? $this->customer->user : $this->createUserForCustomer();

                app(ClientPasswordResetService::class)->generateAndSend($user, $channel);
            });
        } catch (UniqueConstraintViolationException) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'An account with this email already exists.']);

            return;
        } catch (RuntimeException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return;
        }

        $this->customer->refresh()->load('user');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'New password generated and sent.']);
    }

    /**
     * The customer record is the source of truth for real contact info — a
     * linked user's email may be a synthesized placeholder for phone-only signups.
     */
    private function channelDestination(string $channel): ?string
    {
        return $channel === 'email' ? $this->customer->email : $this->customer->phone;
    }

    /**
     * users.email is required + unique at the DB level; synthesize a
     * placeholder for phone-only customers so account creation never fails.
     */
    private function createUserForCustomer(): User
    {
        $email = $this->customer->email ?: strtolower($this->customer->customer_id) . '@clients.local';

        $user = User::create([
            'name'     => $this->customer->name,
            'email'    => $email,
            'phone'    => $this->customer->phone,
            'password' => Hash::make(Str::random(48)),
        ]);

        $user->assignRole('client');

        $this->customer->update(['user_id' => $user->id]);

        return $user;
    }

    public function render()
    {
        $sales = $this->customer->propertySales;

        $kpi = [
            'holdings'   => $sales->count(),
            'totalValue' => $sales->sum('net_amount'),
            'paid'       => $sales->where('payment_status', 'paid')->sum('net_amount'),
            'due'        => $sales->whereIn('payment_status', ['pending', 'partial'])->sum('net_amount'),
            'documents'  => $this->customer->doc_no ? 1 : 0,
        ];

        $docFile      = $this->customer->doc_file_id
            ? File::find($this->customer->doc_file_id)
            : null;

        $profileImage = $this->customer->profile_image_id
            ? File::find($this->customer->profile_image_id)
            : null;

        $canEdit = auth()->user()?->can('customer.edit');

        return view('livewire.admin.customers.customer-show', compact('docFile', 'profileImage', 'canEdit', 'sales', 'kpi'))
            ->layout('layouts.admin.admin');
    }
}
