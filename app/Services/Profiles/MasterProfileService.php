<?php

namespace App\Services\Profiles;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\MasterProfile;
use App\Models\MasterProfileRole;
use App\Models\ProfileType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Master profile ↔ module record linking, import and login handling.
 */
class MasterProfileService
{
    // ── Module records ──────────────────────────────────────────

    /** Search module records of a type that are not yet linked to another profile. */
    public function searchModuleRecords(ProfileType $type, string $term = '', int $limit = 15): Collection
    {
        $config = $type->moduleConfig();
        if (! $config) {
            return collect();
        }

        /** @var class-string<Model> $class */
        $class = $config['model'];
        $linkedIds = MasterProfileRole::where('linkable_type', $class)->pluck('linkable_id');

        return $class::query()
            ->whereNotIn('id', $linkedIds)
            ->when($term !== '', function ($q) use ($term) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Model $m) => [
                'id'    => $m->id,
                'name'  => $m->name,
                'code'  => $this->recordCode($m),
                'phone' => $m->phone ?? null,
            ]);
    }

    public function recordCode(?Model $record): ?string
    {
        return match (true) {
            $record instanceof Customer => $record->customer_id,
            $record instanceof Supplier => $record->code,
            $record instanceof Employee => $record->employee_id,
            default                     => null,
        };
    }

    /**
     * Make sure a linked record has its code. Older suppliers were saved before
     * supplier codes existed — give them one in the supplier module's format.
     */
    public function ensureRecordCode(?Model $record): ?string
    {
        if ($record instanceof Supplier && blank($record->code)) {
            $record->update(['code' => $this->nextCode(Supplier::class, 'code', 'SUP-')]);
        }

        return $this->recordCode($record);
    }

    public function recordUrl(?Model $record): ?string
    {
        return match (true) {
            $record instanceof Customer => route('admin.crm.customers.show', $record),
            $record instanceof Supplier => route('admin.supplier.suppliers.view', $record),
            $record instanceof Employee => route('admin.hrm.employees.view', $record),
            default                     => null,
        };
    }

    /** Employee columns editable from a master profile role. */
    public const EMPLOYEE_FIELDS = ['department_id', 'designation_id', 'work_station_id', 'joining_date', 'employment_type', 'basic_salary', 'status'];

    /** Employment options — same as the HRM employee form. */
    public const EMPLOYMENT_TYPES = ['permanent', 'contractual', 'intern', 'daily'];
    public const EMPLOYEE_STATUSES = ['active', 'inactive', 'resigned', 'terminated'];

    /**
     * Key facts of a linked module record, shown on the role card.
     *
     * @return array<string, string>  label => value (empty values skipped)
     */
    public function recordDetails(?Model $record): array
    {
        $money = fn ($v) => $v !== null && (float) $v != 0 ? number_format((float) $v, 2) : null;

        $details = match (true) {
            $record instanceof Employee => [
                'Designation'     => $record->designation?->name,
                'Department'      => $record->department?->name,
                'Work station'    => $record->workStation?->name,
                'Joining date'    => $record->joining_date?->format('d M Y'),
                'Employment type' => $record->employment_type ? ucfirst($record->employment_type) : null,
                'Basic salary'    => $money($record->basic_salary),
                'Status'          => $record->status ? ucfirst($record->status) : null,
            ],
            $record instanceof Customer => [
                'Customer type' => $record->type ? ucfirst($record->type) : null,
                'KYC status'    => $record->kyc_status ? ucfirst($record->kyc_status) : null,
                'District'      => $record->district,
                'Status'        => $record->status ? ucfirst($record->status) : null,
            ],
            $record instanceof Supplier => [
                'Contact person'  => $record->contact_person,
                'Trade licence'   => $record->trade_license_no,
                'TIN / BIN'       => collect([$record->tin_no, $record->bin_no])->filter()->join(' / ') ?: null,
                'Payment terms'   => $record->payment_terms_days ? $record->payment_terms_days . ' days' : null,
                'Credit limit'    => $money($record->credit_limit),
                'Status'          => $record->is_blocked ? 'Blocked' : ($record->status ? 'Active' : 'Inactive'),
            ],
            default => [],
        };

        return array_filter($details, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Give a customer / employee record the login user, when the record has no user
     * yet and the user isn't already attached to another record of that kind
     * (customers.user_id and employees.user_id are unique).
     */
    public function syncUserToRecord(?Model $record, ?int $userId): void
    {
        if (! $userId || ! ($record instanceof Customer || $record instanceof Employee) || $record->user_id) {
            return;
        }

        $taken = $record::query()->where('user_id', $userId)->whereKeyNot($record->getKey());
        if ($record instanceof Customer) {
            $taken->withTrashed();
        }

        if (! $taken->exists()) {
            $record->update(['user_id' => $userId]);
        }
    }

    /** Whether a user is free to become this profile's login (not used by another master profile). */
    public function userIsFree(int $userId, ?int $exceptProfileId = null): bool
    {
        return ! MasterProfile::where('user_id', $userId)
            ->when($exceptProfileId, fn ($q) => $q->whereKeyNot($exceptProfileId))
            ->exists();
    }

    // ── Delete protection ───────────────────────────────────────

    /**
     * Business data that depends on each module record. Profiles (or roles) whose
     * linked record has any of these cannot be deleted — only deactivated.
     * [table, foreign key column, label]
     */
    private const DEPENDENTS = [
        Customer::class => [
            ['property_sales', 'customer_id', 'property sale'],
        ],
        Supplier::class => [
            ['purchase_orders', 'supplier_id', 'purchase order'],
            ['purchase_invoices', 'supplier_id', 'purchase invoice'],
            ['purchase_returns', 'supplier_id', 'purchase return'],
            ['stock_receives', 'supplier_id', 'stock receive'],
        ],
        Employee::class => [
            ['payrolls', 'employee_id', 'payroll'],
            ['employee_advances', 'employee_id', 'employee advance'],
            ['salary_structures', 'employee_id', 'salary structure'],
            ['properties', 'engineer_id', 'assigned property'],
        ],
    ];

    /**
     * Human-readable reasons a linked record is "in use" (accounts, sales, purchases, payroll…).
     *
     * @return string[]
     */
    public function recordUsage(?Model $record): array
    {
        if (! $record) {
            return [];
        }

        $reasons = [];
        foreach (self::DEPENDENTS[$record::class] ?? [] as [$table, $column, $label]) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $count = DB::table($table)->where($column, $record->getKey())->count();
            if ($count > 0) {
                $reasons[] = $count . ' ' . Str::plural($label, $count);
            }
        }

        // Accounting entries that point straight at the record.
        $transactions = DB::table('transactions')->where('reference_type', $record::class)->where('reference_id', $record->getKey())->count();
        if ($transactions > 0) {
            $reasons[] = $transactions . ' accounting ' . Str::plural('transaction', $transactions);
        }

        $requests = DB::table('banking_payment_requests')->where('sourceable_type', $record::class)->where('sourceable_id', $record->getKey())->count();
        if ($requests > 0) {
            $reasons[] = $requests . ' payment ' . Str::plural('request', $requests);
        }

        return $reasons;
    }

    /**
     * Why a master profile can't be deleted — one line per linked record in use.
     *
     * @return string[]
     */
    public function deletionBlockers(MasterProfile $profile): array
    {
        $blockers = [];

        // Accounting entries tagged directly with this profile.
        $tagged = DB::table('transactions')->where('master_profile_id', $profile->id)->count()
            + DB::table('banking_payment_requests')->where('master_profile_id', $profile->id)->count();
        if ($tagged > 0) {
            $blockers[] = "Profile: {$tagged} accounting " . Str::plural('entry', $tagged);
        }

        foreach ($profile->roles()->with(['type', 'linkable'])->get() as $role) {
            $usage = $this->recordUsage($role->linkable);
            if ($usage) {
                $label = trim(($role->type?->name ?? 'Record') . ' ' . ($this->recordCode($role->linkable) ?? ''));
                $blockers[] = $label . ': ' . implode(', ', $usage);
            }
        }

        return $blockers;
    }

    /** Map module gender values to the profile's allowed set. */
    public function normalizeGender(?string $gender): ?string
    {
        $gender = Str::lower(trim((string) $gender));

        return in_array($gender, ['male', 'female', 'other', 'prefer_not_to_say'], true) ? $gender : null;
    }

    /**
     * Create a new module record (Customer / Supplier / Employee) from the master profile data.
     * $extra holds module-specific fields (e.g. employee department, designation, salary).
     */
    public function createModuleRecord(ProfileType $type, MasterProfile $profile, array $extra = []): ?Model
    {
        $record = match ($type->module) {
            'customer' => Customer::create([
                'type'         => $profile->kind === 'company' ? 'company' : 'individual',
                'name'         => $profile->name,
                'phone'        => $profile->phone ?: 'N/A',
                'phone_alt'    => $profile->phone_alt,
                'email'        => $profile->email,
                'gender'       => $profile->gender ?: null,
                'date_of_birth' => $profile->date_of_birth,
                'address'      => $profile->address,
                'district'     => $profile->district,
                'company_name' => $profile->organization,
                'doc_type'     => in_array($profile->doc_type, ['nid', 'passport', 'driving_licence', 'birth_certificate', 'trade_licence'], true) ? $profile->doc_type : null,
                'doc_no'       => $profile->doc_no,
                'company_tax_id' => $profile->tin_no,
                'status'       => 'active',
                'source'       => 'other',
            ]),
            'supplier' => Supplier::create([
                'name'           => $profile->organization ?: $profile->name,
                'code'           => $this->nextCode(Supplier::class, 'code', 'SUP-'),
                'contact_person' => $profile->organization ? $profile->name : null,
                'phone'          => $profile->phone,
                'alternate_phone' => $profile->phone_alt,
                'email'          => $profile->email,
                'address'        => $profile->address,
                'tin_no'         => $profile->tin_no,
                'status'         => true,
                'created_by'     => auth()->id(),
            ]),
            'employee' => Employee::create(array_merge([
                'employee_id'   => $this->nextCode(Employee::class, 'employee_id', 'EMP-'),
                'name'          => $profile->name,
                'phone'         => $profile->phone,
                'email'         => $profile->email,
                // HRM only knows male / female / other
                'gender'        => in_array($profile->gender, ['male', 'female', 'other'], true) ? $profile->gender : null,
                'date_of_birth' => $profile->date_of_birth,
                'joining_date'  => now()->toDateString(),
                'status'        => 'active',
                'address'       => $profile->address,
            ], array_intersect_key($extra, array_flip(self::EMPLOYEE_FIELDS)))),
            default => null,
        };

        $this->syncUserToRecord($record, $profile->user_id);

        return $record;
    }

    private function nextCode(string $class, string $column, string $prefix): string
    {
        $n = (int) $class::query()->count() + 1;
        do {
            $code = $prefix . str_pad((string) $n++, 6, '0', STR_PAD_LEFT);
            $query = $class::query();
            if (method_exists($class, 'bootSoftDeletes')) {
                $query->withTrashed();
            }
        } while ($query->where($column, $code)->exists());

        return $code;
    }

    // ── Import ──────────────────────────────────────────────────

    /**
     * Import unlinked Customer / Supplier / Employee records into master profiles.
     * Records are merged into an existing profile when phone or email matches.
     *
     * @return array<string,int> created/linked counts per module
     */
    public function importFromModules(?array $modules = null): array
    {
        $result = ['created' => 0, 'linked' => 0];

        DB::transaction(function () use ($modules, &$result) {
            $types = ProfileType::whereNotNull('module')
                ->when($modules, fn ($q) => $q->whereIn('module', $modules))
                ->get();

            foreach ($types as $type) {
                $class = $type->moduleConfig()['model'] ?? null;
                if (! $class) {
                    continue;
                }

                $linkedIds = MasterProfileRole::where('linkable_type', $class)->pluck('linkable_id')->all();

                $class::query()->whereNotIn('id', $linkedIds)->orderBy('id')->each(function (Model $record) use ($type, &$result) {
                    $phone = $this->normalizePhone($record->phone ?? null);
                    $email = $record->email ? Str::lower(trim($record->email)) : null;

                    $profile = MasterProfile::query()
                        ->where(function ($q) use ($phone, $email) {
                            $q->whereRaw('1 = 0');
                            if ($phone) {
                                $q->orWhere('phone', $phone);
                            }
                            if ($email) {
                                $q->orWhere('email', $email);
                            }
                        })
                        ->first();

                    if ($profile && $profile->roles()->where('profile_type_id', $type->id)->exists()) {
                        $profile = null; // already holds this role — keep records separate
                    }

                    if (! $profile) {
                        $profile = MasterProfile::create($this->profileDataFromRecord($record) + [
                            'phone' => $phone, 'email' => $email, 'created_by' => auth()->id(),
                        ]);
                        $result['created']++;
                    } else {
                        $result['linked']++;
                    }

                    $profile->roles()->create([
                        'profile_type_id' => $type->id,
                        'linkable_type'   => $record::class,
                        'linkable_id'     => $record->id,
                        'reference'       => $this->ensureRecordCode($record),
                        'status'          => 'active',
                    ]);

                    if (! $profile->user_id && ! empty($record->user_id) && $this->userIsFree($record->user_id, $profile->id)) {
                        $profile->update(['user_id' => $record->user_id]);
                    }
                });
            }
        });

        return $result;
    }

    private function profileDataFromRecord(Model $record): array
    {
        return match (true) {
            $record instanceof Customer => [
                'kind' => $record->type === 'company' ? 'company' : 'individual', 'name' => $record->name,
                'organization' => $record->company_name, 'phone_alt' => $record->phone_alt, 'gender' => $this->normalizeGender($record->gender),
                'date_of_birth' => $record->date_of_birth, 'doc_type' => $record->doc_type, 'doc_no' => $record->doc_no,
                'tin_no' => $record->company_tax_id, 'address' => $record->address, 'district' => $record->district,
            ],
            $record instanceof Supplier => [
                'kind' => 'company', 'name' => $record->name, 'organization' => $record->contact_person,
                'phone_alt' => $record->alternate_phone, 'tin_no' => $record->tin_no, 'address' => $record->address,
            ],
            $record instanceof Employee => [
                'kind' => 'individual', 'name' => $record->name, 'gender' => $this->normalizeGender($record->gender),
                'date_of_birth' => $record->date_of_birth, 'address' => $record->address,
            ],
            default => ['name' => $record->name ?? 'Unknown'],
        };
    }

    public function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        return $phone === '' || $phone === 'N/A' ? null : preg_replace('/[^\d+]/', '', $phone);
    }

    // ── Login ───────────────────────────────────────────────────

    /** Create a login user for the profile and attach it. */
    public function createLogin(MasterProfile $profile, string $email, string $password, ?string $role, array $panelIds = []): User
    {
        return DB::transaction(function () use ($profile, $email, $password, $role, $panelIds) {
            $user = User::create([
                'name'     => $profile->name,
                'email'    => $email,
                'phone'    => $profile->phone,
                'address'  => $profile->address,
                'password' => Hash::make($password),
            ]);

            if ($role) {
                $user->syncRoles([$role]);
            }
            $user->panels()->sync($panelIds);

            $this->attachUser($profile, $user);

            return $user;
        });
    }

    /** Link an existing user and propagate it to linked customer / employee records. */
    public function attachUser(MasterProfile $profile, User $user): void
    {
        $profile->update(['user_id' => $user->id]);

        foreach ($profile->roles()->with('linkable')->get() as $role) {
            $this->syncUserToRecord($role->linkable, $user->id);
        }
    }
}
