<?php

namespace App\Livewire\Admin\Profiles;

use App\Livewire\Admin\Profiles\Concerns\HasProfileForm;
use App\Models\MasterProfile;
use App\Models\ProfileType;
use App\Services\Profiles\MasterProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProfileIndex extends Component
{
    use HasProfileForm, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    #[Url(as: 'login', except: '')]
    public string $loginFilter = '';

    public bool $showCreateModal = false;
    public array $selectedTypes = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('profile.view'), 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'typeFilter', 'statusFilter', 'loginFilter'], true)) {
            $this->resetPage();
        }
    }

    public function setType(string $slug): void
    {
        $this->typeFilter = $this->typeFilter === $slug ? '' : $slug;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'typeFilter', 'statusFilter', 'loginFilter');
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->can('profile.create'), 403);
        $this->resetValidation();
        $this->form = $this->blankProfileForm();
        $type = $this->typeFilter ? ProfileType::where('slug', $this->typeFilter)->value('id') : null;
        $this->selectedTypes = $type ? [(string) $type] : [];
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function createProfile(MasterProfileService $service)
    {
        abort_unless(auth()->user()?->can('profile.create'), 403);

        $this->validate($this->profileRules() + [
            'selectedTypes'   => ['array'],
            'selectedTypes.*' => ['integer', 'exists:profile_types,id'],
        ], [], $this->profileAttributes());

        $profile = DB::transaction(function () use ($service) {
            $profile = MasterProfile::create($this->profilePayload() + ['created_by' => auth()->id()]);

            foreach (ProfileType::whereIn('id', $this->selectedTypes)->get() as $type) {
                // Module types get a fresh linked record (customer / supplier / employee).
                $record = $type->module ? $service->createModuleRecord($type, $profile) : null;
                $profile->roles()->create([
                    'profile_type_id' => $type->id,
                    'linkable_type'   => $record ? $record::class : null,
                    'linkable_id'     => $record?->id,
                    'reference'       => $service->recordCode($record),
                    'since'           => now()->toDateString(),
                    'status'          => 'active',
                ]);
            }

            return $profile;
        });

        $this->dispatch('toast', type: 'success', message: 'Profile created');

        return $this->redirectRoute('admin.profiles.show', $profile, navigate: false);
    }

    public function importExisting(MasterProfileService $service): void
    {
        abort_unless(auth()->user()?->can('profile.create'), 403);

        $result = $service->importFromModules();
        $tagged = app(\App\Services\Profiles\ProfileTransactionResolver::class)->sync();

        $this->dispatch('toast', type: 'success',
            message: "Import done — {$result['created']} new profiles, {$result['linked']} merged, {$tagged} accounting entries linked.");
    }

    public function render(): View
    {
        $types = ProfileType::active()->ordered()->withCount('roles')->get();

        $profiles = MasterProfile::query()
            ->with(['roles.type', 'user:id,email'])
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('code', 'like', $term)
                    ->orWhere('phone', 'like', $term)->orWhere('email', 'like', $term)
                    ->orWhere('organization', 'like', $term)->orWhere('doc_no', 'like', $term));
            })
            ->when($this->typeFilter !== '', fn ($q) => $q->whereHas('roles.type', fn ($t) => $t->where('slug', $this->typeFilter)))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->loginFilter === 'yes', fn ($q) => $q->whereNotNull('user_id'))
            ->when($this->loginFilter === 'no', fn ($q) => $q->whereNull('user_id'))
            ->latest('id')
            ->paginate(12);

        $stats = [
            'total'    => MasterProfile::count(),
            'active'   => MasterProfile::where('status', 'active')->count(),
            'login'    => MasterProfile::whereNotNull('user_id')->count(),
            'multi'    => MasterProfile::has('roles', '>', 1)->count(),
            'unlinked' => $this->unlinkedCount(),
        ];

        return view('livewire.admin.profiles.profile-index', [
            'types'    => $types,
            'profiles' => $profiles,
            'balances' => app(\App\Services\Profiles\ProfileLedgerService::class)->balancesFor($profiles->pluck('id')),
            'stats'    => $stats,
            'docTypes' => self::docTypes(),
        ])->layout('layouts.admin.admin');
    }

    /** Module records (customers, suppliers, employees) not yet in the master. */
    private function unlinkedCount(): int
    {
        $count = 0;
        foreach (ProfileType::MODULES as $config) {
            $count += $config['model']::query()
                ->whereNotIn('id', DB::table('master_profile_roles')->where('linkable_type', $config['model'])->select('linkable_id'))
                ->count();
        }

        return $count;
    }
}
