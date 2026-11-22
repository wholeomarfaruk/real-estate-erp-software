<?php

namespace App\Livewire\Admin\Profiles;

use App\Models\MasterProfile;
use App\Models\ProfileType;
use App\Services\Profiles\ProfileLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Profile-wise balance report: received / paid / net for every master profile. */
class ProfileBalances extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    /** with = only profiles that have entries, all = every profile */
    #[Url(as: 'show', except: 'with')]
    public string $show = 'with';

    #[Url(as: 'sort', except: 'net')]
    public string $sort = 'net';

    #[Url(as: 'dir', except: 'desc')]
    public string $dir = 'desc';

    private const SORTS = ['name', 'received', 'paid', 'net', 'entries', 'last_at'];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('profile.view'), 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'typeFilter', 'show'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, self::SORTS, true)) {
            return;
        }
        $this->dir = $this->sort === $column && $this->dir === 'desc' ? 'asc' : 'desc';
        if ($this->sort !== $column) {
            $this->dir = $column === 'name' ? 'asc' : 'desc';
        }
        $this->sort = $column;
        $this->resetPage();
    }

    protected function query(ProfileLedgerService $ledger)
    {
        $balances = $ledger->balancesQuery();

        $query = MasterProfile::query()
            ->leftJoinSub($balances, 'b', 'b.master_profile_id', '=', 'master_profiles.id')
            ->select([
                'master_profiles.*',
                DB::raw('COALESCE(b.received, 0) as received'),
                DB::raw('COALESCE(b.paid, 0) as paid'),
                DB::raw('COALESCE(b.net, 0) as net'),
                DB::raw('COALESCE(b.entries, 0) as entries'),
                'b.last_at',
            ])
            ->when($this->show === 'with', fn ($q) => $q->whereNotNull('b.master_profile_id'))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(fn ($w) => $w->where('master_profiles.name', 'like', $term)
                    ->orWhere('master_profiles.code', 'like', $term)
                    ->orWhere('master_profiles.phone', 'like', $term));
            })
            ->when($this->typeFilter !== '', fn ($q) => $q->whereHas('roles.type', fn ($t) => $t->where('slug', $this->typeFilter)));

        return $query;
    }

    public function render(ProfileLedgerService $ledger): View
    {
        $sort = in_array($this->sort, self::SORTS, true) ? $this->sort : 'net';
        $dir = $this->dir === 'asc' ? 'asc' : 'desc';

        $rows = $this->query($ledger)
            ->with('roles.type')
            ->orderBy($sort === 'name' ? 'master_profiles.name' : $sort, $dir)
            ->orderBy('master_profiles.name')
            ->paginate(25);

        // Totals across the whole filtered set (not just this page)
        $totals = DB::query()->fromSub($this->query($ledger)->toBase(), 'f')
            ->selectRaw('COUNT(*) as profiles, COALESCE(SUM(received),0) as received, COALESCE(SUM(paid),0) as paid, COALESCE(SUM(net),0) as net')
            ->first();

        return view('livewire.admin.profiles.profile-balances', [
            'rows'   => $rows,
            'totals' => $totals,
            'types'  => ProfileType::active()->ordered()->get(['id', 'name', 'slug', 'icon', 'tone']),
        ])->layout('layouts.admin.admin');
    }
}
