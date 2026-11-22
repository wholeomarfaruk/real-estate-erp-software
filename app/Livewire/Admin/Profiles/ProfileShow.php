<?php

namespace App\Livewire\Admin\Profiles;

use App\Livewire\Admin\Profiles\Concerns\HasProfileForm;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\WorkStation;
use App\Models\MasterProfile;
use App\Models\MasterProfileRole;
use App\Models\Panel;
use App\Models\ProfileType;
use App\Models\User;
use App\Services\Profiles\MasterProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class ProfileShow extends Component
{
    use HasProfileForm;

    public MasterProfile $profile;

    // Edit profile
    public bool $showEditModal = false;

    // Add / edit role
    public bool $showRoleModal = false;
    public ?int $editingRoleId = null;
    public string $role_type_id = '';
    public string $role_mode = 'create';   // create | link | none
    public string $role_search = '';
    public ?int $role_link_id = null;
    public string $role_reference = '';
    public string $role_since = '';
    public string $role_status = 'active';
    public array $role_data = [];

    // Employee details (saved to the linked HRM employee record)
    public array $emp = [];

    // Login
    public bool $showLoginModal = false;
    public string $login_mode = 'create';  // create | link
    public string $login_email = '';
    public string $login_password = '';
    public string $login_role = '';
    public array $login_panels = [];
    public string $login_user_search = '';
    public ?int $login_user_id = null;
    public ?string $newPassword = null;


    public function mount(MasterProfile $profile): void
    {
        abort_unless(auth()->user()?->can('profile.view'), 403);
        $this->profile = $profile;
    }

    // ── Profile ────────────────────────────────────────────────

    public function openEditModal(): void
    {
        $this->authorizeTo('profile.edit');
        $this->resetValidation();
        $this->fillProfileForm($this->profile);
        $this->showEditModal = true;
    }

    public function saveProfile(): void
    {
        $this->authorizeTo('profile.edit');
        $this->validate($this->profileRules($this->profile), [], $this->profileAttributes());
        $this->profile->update($this->profilePayload());
        $this->showEditModal = false;
        $this->dispatch('toast', type: 'success', message: 'Profile updated');
    }

    public function toggleStatus(): void
    {
        $this->authorizeTo('profile.edit');
        $this->profile->update(['status' => $this->profile->status === 'active' ? 'inactive' : 'active']);
    }

    public function deleteProfile(MasterProfileService $service)
    {
        $this->authorizeTo('profile.delete');

        // Profiles tied to accounting / sales / purchase / payroll data can only be deactivated.
        if ($blockers = $service->deletionBlockers($this->profile)) {
            $this->dispatch('toast', type: 'error', message: 'This profile has linked business records and cannot be deleted. Deactivate it instead.');
            return null;
        }

        $this->profile->roles()->delete();
        $this->profile->delete();
        $this->dispatch('toast', type: 'success', message: 'Profile deleted. Linked module records were kept.');

        return $this->redirectRoute('admin.profiles.index');
    }

    // ── Roles ──────────────────────────────────────────────────

    public function openRoleModal(?int $roleId = null): void
    {
        $this->authorizeTo('profile.edit');
        $this->resetValidation();
        $this->reset('editingRoleId', 'role_type_id', 'role_search', 'role_link_id', 'role_reference', 'role_status', 'role_data');
        $this->role_mode = 'create';
        $this->role_since = now()->toDateString();

        $this->emp = $this->blankEmployee();

        if ($roleId) {
            $role = $this->profile->roles()->with('linkable')->findOrFail($roleId);
            $this->editingRoleId = $role->id;
            $this->role_type_id = (string) $role->profile_type_id;
            $this->role_reference = (string) $role->reference;
            $this->role_since = $role->since?->toDateString() ?? '';
            $this->role_status = $role->status;
            $this->role_data = $role->data ?? [];
            $this->role_mode = 'none';

            if ($role->linkable instanceof Employee) {
                $e = $role->linkable;
                $this->emp = [
                    'department_id'   => (string) $e->department_id,
                    'designation_id'  => (string) $e->designation_id,
                    'work_station_id' => (string) $e->work_station_id,
                    'joining_date'    => $e->joining_date?->toDateString() ?? '',
                    'employment_type' => (string) $e->employment_type,
                    'basic_salary'    => (string) (float) $e->basic_salary,
                    'status'          => $e->status ?: 'active',
                ];
            }
        } else {
            $first = $this->availableTypes()->first();
            $this->role_type_id = $first ? (string) $first->id : '';
            $this->updatedRoleTypeId();
        }

        $this->showRoleModal = true;
    }

    public function updatedRoleTypeId(): void
    {
        $type = $this->selectedRoleType();
        $this->role_mode = $type?->module ? 'create' : 'none';
        $this->role_link_id = null;
        $this->role_data = collect($type?->fields ?? [])->mapWithKeys(fn ($f) => [$f['key'] => $this->role_data[$f['key']] ?? ''])->all();
    }

    protected function blankEmployee(): array
    {
        return [
            'department_id'   => '',
            'designation_id'  => '',
            'work_station_id' => (string) (WorkStation::where('status', true)->orderBy('name')->value('id') ?? ''),
            'joining_date'    => now()->toDateString(),
            'employment_type' => 'permanent',
            'basic_salary'    => '0',
            'status'          => 'active',
        ];
    }

    /** Designation list follows the chosen department. */
    public function updatedEmpDepartmentId(): void
    {
        $this->emp['designation_id'] = '';
    }

    /** Whether the role modal is editing employee details (new employee, or a linked one). */
    protected function editsEmployee(?ProfileType $type): bool
    {
        if ($type?->module !== 'employee') {
            return false;
        }

        return $this->editingRoleId
            ? $this->profile->roles()->whereKey($this->editingRoleId)->where('linkable_type', Employee::class)->exists()
            : $this->role_mode === 'create';
    }

    protected function employeeRules(): array
    {
        return [
            'emp.department_id'   => ['nullable', 'exists:departments,id'],
            'emp.designation_id'  => ['nullable', 'exists:designations,id'],
            'emp.work_station_id' => ['required', 'exists:work_stations,id'],
            'emp.joining_date'    => ['required', 'date'],
            'emp.employment_type' => ['nullable', Rule::in(MasterProfileService::EMPLOYMENT_TYPES)],
            'emp.basic_salary'    => ['required', 'numeric', 'min:0'],
            'emp.status'          => ['required', Rule::in(MasterProfileService::EMPLOYEE_STATUSES)],
        ];
    }

    protected function employeePayload(): array
    {
        return collect($this->emp)->only(MasterProfileService::EMPLOYEE_FIELDS)
            ->map(fn ($v) => $v === '' ? null : $v)
            ->all();
    }

    public function selectLinkRecord(int $id): void
    {
        $this->role_link_id = $id;
    }

    public function saveRole(MasterProfileService $service): void
    {
        $this->authorizeTo('profile.edit');
        $type = $this->selectedRoleType();

        $rules = [
            'role_type_id'   => ['required', 'exists:profile_types,id'],
            'role_reference' => ['nullable', 'string', 'max:60'],
            'role_since'     => ['nullable', 'date'],
            'role_status'    => ['required', Rule::in(['active', 'inactive'])],
        ];
        if (! $this->editingRoleId) {
            $rules['role_type_id'][] = Rule::unique('master_profile_roles', 'profile_type_id')->where('master_profile_id', $this->profile->id);
            if ($type?->module && $this->role_mode === 'link') {
                $rules['role_link_id'] = ['required', 'integer'];
            }
        }
        foreach ($type?->fields ?? [] as $field) {
            $rules['role_data.' . $field['key']] = array_values(array_filter([
                ! empty($field['required']) ? 'required' : 'nullable',
                match ($field['type']) { 'number' => 'numeric', 'date' => 'date', default => 'string' },
                $field['type'] === 'textarea' ? 'max:2000' : ($field['type'] === 'text' ? 'max:255' : null),
                $field['type'] === 'select'
                    ? Rule::in(array_values(array_filter(array_map('trim', explode(',', (string) ($field['options'] ?? ''))))))
                    : null,
            ]));
        }

        $editsEmployee = $this->editsEmployee($type);
        if ($editsEmployee) {
            $rules += $this->employeeRules();
        }

        $this->validate($rules, [
            'role_type_id.unique'   => 'This profile already has that role.',
            'role_link_id.required' => 'Pick a record to link.',
        ], collect($type?->fields ?? [])->mapWithKeys(fn ($f) => ['role_data.' . $f['key'] => $f['label']])->all() + [
            'role_type_id' => 'role', 'emp.work_station_id' => 'work station', 'emp.joining_date' => 'joining date',
            'emp.basic_salary' => 'basic salary', 'emp.department_id' => 'department', 'emp.designation_id' => 'designation',
        ]);

        // A designation tied to a department must match the chosen department
        // (designations without a department fit any department).
        if ($editsEmployee && $this->emp['designation_id'] && $this->emp['department_id']) {
            $designationDept = Designation::whereKey($this->emp['designation_id'])->value('department_id');
            if ($designationDept && (string) $designationDept !== (string) $this->emp['department_id']) {
                $this->addError('emp.designation_id', 'This designation belongs to another department.');
                return;
            }
        }

        $data = collect($this->role_data)->map(fn ($v) => $v === '' ? null : $v)->all();

        if ($this->editingRoleId) {
            DB::transaction(function () use ($data, $editsEmployee) {
                $role = $this->profile->roles()->with('linkable')->findOrFail($this->editingRoleId);
                $role->update([
                    'reference' => $this->role_reference ?: null,
                    'since'     => $this->role_since ?: null,
                    'status'    => $this->role_status,
                    'data'      => $data,
                ]);

                if ($editsEmployee && $role->linkable instanceof Employee) {
                    $role->linkable->update($this->employeePayload());
                }
            });
        } else {
            $linkRecord = null;
            if ($type->module && $this->role_mode === 'link') {
                $linkRecord = $type->moduleConfig()['model']::find($this->role_link_id);
                if (! $linkRecord) {
                    $this->addError('role_link_id', 'That record no longer exists.');
                    return;
                }
                if (MasterProfileRole::where('linkable_type', $linkRecord::class)->where('linkable_id', $linkRecord->id)->exists()) {
                    $this->addError('role_link_id', 'That record is already linked to another profile.');
                    return;
                }
            }

            // Record creation + role row succeed or fail together.
            DB::transaction(function () use ($service, $type, $linkRecord, $data, $editsEmployee) {
                $record = $linkRecord;
                if ($type->module && $this->role_mode === 'create') {
                    $record = $service->createModuleRecord($type, $this->profile, $editsEmployee ? $this->employeePayload() : []);
                }

                $this->profile->roles()->create([
                    'profile_type_id' => $type->id,
                    'linkable_type'   => $record ? $record::class : null,
                    'linkable_id'     => $record?->id,
                    'reference'       => $this->role_reference ?: $service->ensureRecordCode($record),
                    'since'           => $this->role_since ?: null,
                    'status'          => $this->role_status,
                    'data'            => $data,
                ]);

                if (! $record) {
                    return;
                }

                // Existing entries of a linked record now belong to this profile.
                app(\App\Services\Profiles\ProfileTransactionResolver::class)->sync($this->profile->id);

                // Share the login both ways between the profile and the linked record.
                if ($this->profile->user_id) {
                    $service->syncUserToRecord($record, $this->profile->user_id);
                } elseif (! empty($record->user_id) && $service->userIsFree($record->user_id, $this->profile->id)) {
                    $this->profile->update(['user_id' => $record->user_id]);
                }
            });
        }

        $this->showRoleModal = false;
        $this->dispatch('toast', type: 'success', message: $this->editingRoleId ? 'Role updated' : 'Role added');
    }

    public function removeRole(MasterProfileService $service, int $roleId): void
    {
        $this->authorizeTo('profile.edit');
        $role = $this->profile->roles()->with('linkable')->findOrFail($roleId);

        // Keep the link when the record is in use — set the role inactive instead.
        if ($service->recordUsage($role->linkable)) {
            $this->dispatch('toast', type: 'error', message: 'This role\'s record has linked business data and cannot be removed. Set the role to Inactive instead.');
            return;
        }

        $role->delete();
        $this->dispatch('toast', type: 'success', message: 'Role removed. The linked module record was kept.');
    }

    protected function selectedRoleType(): ?ProfileType
    {
        return $this->role_type_id ? ProfileType::find($this->role_type_id) : null;
    }

    protected function availableTypes()
    {
        $taken = $this->profile->roles()->pluck('profile_type_id');

        return ProfileType::active()->ordered()->when(! $this->editingRoleId, fn ($q) => $q->whereNotIn('id', $taken))->get();
    }

    // ── Login ──────────────────────────────────────────────────

    public function openLoginModal(): void
    {
        $this->authorizeTo('profile.login.manage');
        $this->resetValidation();
        $this->login_mode = 'create';
        $this->login_email = (string) $this->profile->email;
        $this->login_password = Str::password(10, symbols: false);
        $this->login_role = $this->profile->roles()->with('type')->get()->pluck('type.login_role')->filter()->first() ?? '';
        $this->login_panels = [];
        $this->login_user_search = '';
        $this->login_user_id = null;
        $this->showLoginModal = true;
    }

    public function generatePassword(): void
    {
        $this->login_password = Str::password(10, symbols: false);
    }

    /** Roles only a superadmin may grant or manage. */
    protected const PROTECTED_ROLES = ['superadmin', 'admin'];

    /** Role names the current user may assign to a profile login. */
    protected function assignableRoles()
    {
        $roles = Role::orderBy('name')->pluck('name');

        return auth()->user()?->hasRole('superadmin')
            ? $roles
            : $roles->reject(fn ($r) => in_array($r, self::PROTECTED_ROLES, true))->values();
    }

    /** A non-superadmin may not take over (link / reset / re-role) a privileged account. */
    protected function canManageUser(User $user): bool
    {
        return auth()->user()?->hasRole('superadmin') || ! $user->hasAnyRole(self::PROTECTED_ROLES);
    }

    public function saveLogin(MasterProfileService $service): void
    {
        $this->authorizeTo('profile.login.manage');

        if ($this->profile->fresh()->user_id) {
            $this->addError('login_email', 'This profile already has a login. Unlink it first.');
            return;
        }

        if ($this->login_mode === 'link') {
            $this->validate(['login_user_id' => ['required', 'exists:users,id']], ['login_user_id.required' => 'Pick a user to link.']);
            $user = User::findOrFail($this->login_user_id);

            if (! $service->userIsFree($user->id, $this->profile->id)) {
                $this->addError('login_user_id', 'That user is already linked to another profile.');
                return;
            }
            if (! $this->canManageUser($user)) {
                $this->addError('login_user_id', 'Only a superadmin can link an admin account.');
                return;
            }

            $service->attachUser($this->profile, $user);
        } else {
            $this->validate([
                'login_email'    => ['required', 'email', 'max:150', 'unique:users,email'],
                'login_password' => ['required', 'string', 'min:8'],
                'login_role'     => ['nullable', Rule::in($this->assignableRoles()->all())],
                'login_panels'   => ['array'],
                'login_panels.*' => ['integer', 'exists:panels,id'],
            ], [], ['login_email' => 'email', 'login_password' => 'password', 'login_role' => 'role']);

            $service->createLogin($this->profile, mb_strtolower($this->login_email), $this->login_password, $this->login_role ?: null, $this->login_panels);
            if (! $this->profile->email) {
                $this->profile->update(['email' => mb_strtolower($this->login_email)]);
            }
        }

        $this->profile->refresh();
        $this->showLoginModal = false;
        $this->dispatch('toast', type: 'success', message: 'Login account ready');
    }

    public function resetLoginPassword(): void
    {
        $this->authorizeTo('profile.login.manage');
        $user = $this->profile->user;
        abort_unless($user, 404);
        abort_unless($this->canManageUser($user), 403, 'Only a superadmin can reset an admin password.');

        $this->login_password = Str::password(10, symbols: false);
        $user->update(['password' => Hash::make($this->login_password)]);
        $this->newPassword = $this->login_password; // shown once in the login card
    }

    public function updateLoginRole(string $role): void
    {
        $this->authorizeTo('profile.login.manage');
        $user = $this->profile->user;
        abort_unless($user, 404);

        if (! $this->canManageUser($user) || ($role !== '' && ! $this->assignableRoles()->contains($role))) {
            $this->dispatch('toast', type: 'error', message: 'You are not allowed to assign that role.');
            return;
        }

        $role === '' ? $user->syncRoles([]) : $user->syncRoles([$role]);
        $this->dispatch('toast', type: 'success', message: 'Login role updated');
    }

    public function unlinkLogin(): void
    {
        $this->authorizeTo('profile.login.manage');
        $this->newPassword = null;
        $this->profile->update(['user_id' => null]);
        $this->dispatch('toast', type: 'success', message: 'Login unlinked from profile. The user account was kept.');
    }

    protected function authorizeTo(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }

    public function render(MasterProfileService $service): View
    {
        $this->profile->load(['roles.type', 'roles.linkable', 'user.roles', 'user.panels']);
        $roleType = $this->showRoleModal ? $this->selectedRoleType() : null;
        $this->profile->roles->each(fn ($r) => $r->linkable instanceof Employee ? $r->linkable->loadMissing(['department', 'designation', 'workStation']) : null);
        $roleUsage = $this->profile->roles->mapWithKeys(fn ($r) => [$r->id => $service->recordUsage($r->linkable)]);
        $roleDetails = $this->profile->roles->mapWithKeys(fn ($r) => [$r->id => $service->recordDetails($r->linkable)]);
        $showEmployeeForm = $this->showRoleModal && $this->editsEmployee($roleType);

        return view('livewire.admin.profiles.profile-show', [
            'roles'        => $this->profile->roles->sortBy(fn ($r) => $r->type?->sort_order),
            'roleUsage'    => $roleUsage,
            'roleLedger'   => app(\App\Services\Profiles\ProfileLedgerService::class)->byRole($this->profile),
            'ledger'       => app(\App\Services\Profiles\ProfileLedgerService::class)->summary($this->profile),
            'roleDetails'  => $roleDetails,
            'showEmployeeForm' => $showEmployeeForm,
            'departments'  => $showEmployeeForm ? Department::where('status', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'designations' => $showEmployeeForm
                ? Designation::where('status', true)
                    ->when($this->emp['department_id'] ?? '', fn ($q, $d) => $q->where(fn ($w) => $w->where('department_id', $d)->orWhereNull('department_id')))
                    ->orderBy('name')->get(['id', 'name'])
                : collect(),
            'workStations' => $showEmployeeForm ? WorkStation::where('status', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'isLocked'     => $roleUsage->contains(fn ($u) => ! empty($u))
                || \Illuminate\Support\Facades\DB::table('transactions')->where('master_profile_id', $this->profile->id)->exists()
                || \Illuminate\Support\Facades\DB::table('banking_payment_requests')->where('master_profile_id', $this->profile->id)->exists(),
            'docTypes'     => self::docTypes(),
            'service'      => $service,
            'roleType'     => $roleType,
            'roleTypes'    => $this->showRoleModal ? $this->availableTypes() : collect(),
            'linkResults'  => $roleType?->module && $this->role_mode === 'link' ? $service->searchModuleRecords($roleType, trim($this->role_search)) : collect(),
            'spatieRoles'  => $this->assignableRoles(),
            'canManageUser' => $this->profile->user ? $this->canManageUser($this->profile->user) : true,
            'panels'       => Panel::orderBy('name')->get(['id', 'name']),
            'userResults'  => $this->showLoginModal && $this->login_mode === 'link'
                ? User::query()
                    ->whereNotIn('id', MasterProfile::whereNotNull('user_id')->pluck('user_id'))
                    ->when(trim($this->login_user_search) !== '', fn ($q) => $q->where(fn ($w) => $w
                        ->where('name', 'like', '%' . trim($this->login_user_search) . '%')
                        ->orWhere('email', 'like', '%' . trim($this->login_user_search) . '%')))
                    ->orderBy('name')->limit(10)->get(['id', 'name', 'email'])
                : collect(),
        ])->layout('layouts.admin.admin');
    }
}
