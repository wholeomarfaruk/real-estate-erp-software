<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Settings extends Component
{
    public $user;
    public $name;
    public $email;
    public $phone;
    public $country_code;
    public $address;
    public $bio;
    public $profile_photo_path;

    public $current_password;
    public $password;
    public $password_confirmation;

    protected $listeners = ['mediaSelected'];

    public function mount()
    {
        $this->user = Auth::user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone;
        $this->country_code = $this->user->country_code;
        $this->address = $this->user->address;
        $this->bio = $this->user->bio;
        $this->profile_photo_path = $this->user->profile_photo_path;
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['name', 'email', 'phone', 'country_code', 'address', 'bio'])) {
            $this->validateOnly($propertyName, $this->rules());
        }
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                \Illuminate\Validation\Rule::unique('users')->ignore($this->user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:5',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'profile_photo_path' => 'nullable|string',
        ];
    }

    public function updateAccount()
    {
        $validated = $this->validate();

        $this->user->update($validated);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Account settings updated successfully!'
        ]);
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($this->current_password, $this->user->password)) {
            $this->addError('current_password', 'The current password is incorrect.');
            return;
        }

        $this->user->update([
            'password' => Hash::make($this->password),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Password updated successfully!'
        ]);
    }

    public function mediaSelected($field, $id)
    {
        if ($field === 'profile_photo_path') {
            $this->profile_photo_path = $id;
            $this->user->update(['profile_photo_path' => $id]);
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Profile photo updated successfully!'
            ]);
        }
    }

    public function removeMedia($field, $id)
    {
        if ($field === 'profile_photo_path') {
            $this->profile_photo_path = null;
            $this->user->update(['profile_photo_path' => null]);
            $this->dispatch('toast', [
                'type' => 'success',
                'message' => 'Profile photo removed successfully!'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.settings.settings')->layout('layouts.admin.admin');
    }
}