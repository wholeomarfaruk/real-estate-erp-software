<?php

namespace App\Livewire\Admin\Accounts\Settings;

use App\Accounting\AccountingEventRegistry;
use App\Enums\Accounts\AccountSource;
use App\Enums\Accounts\PostingLeg;
use App\Models\Account;
use App\Models\AccountingEvent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class AccountingSettings extends Component
{
    public ?int $editingId = null;

    /** Active toggle being edited. */
    public bool $isActive = true;

    /**
     * Editable copy of the selected event's posting rules.
     *
     * @var array<int, array{leg:string, account_source:string, account_id:?int, runtime_slot:?string, description:?string}>
     */
    public array $legs = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('accounts.settings.manage'), 403);
    }

    public function selectEvent(int $id): void
    {
        $event = AccountingEvent::query()->with('rules')->findOrFail($id);

        $this->editingId = $event->id;
        $this->isActive  = (bool) $event->is_active;

        $this->legs = $event->rules
            ->map(fn ($rule): array => [
                'leg'            => $rule->leg->value,
                'account_source' => $rule->account_source->value,
                'account_id'     => $rule->account_id,
                'runtime_slot'   => $rule->runtime_slot,
                'description'    => $rule->description,
            ])
            ->values()
            ->all();
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'legs', 'isActive']);
    }

    public function addLeg(): void
    {
        $this->legs[] = [
            'leg'            => PostingLeg::DEBIT->value,
            'account_source' => AccountSource::FIXED->value,
            'account_id'     => null,
            'runtime_slot'   => null,
            'description'    => null,
        ];
    }

    public function removeLeg(int $index): void
    {
        unset($this->legs[$index]);
        $this->legs = array_values($this->legs);
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('accounts.settings.manage'), 403);

        $event = AccountingEvent::query()->findOrFail($this->editingId);
        $slots = array_keys(AccountingEventRegistry::slots($event->key));

        // --- validation ------------------------------------------------------
        $hasDebit = false;
        $hasCredit = false;

        foreach ($this->legs as $i => $leg) {
            $field = "legs.$i.";

            if (! in_array($leg['leg'] ?? '', ['debit', 'credit'], true)) {
                $this->addError($field.'leg', 'Choose debit or credit.');
            } else {
                $leg['leg'] === 'debit' ? $hasDebit = true : $hasCredit = true;
            }

            if (($leg['account_source'] ?? '') === 'fixed') {
                $accountId = (int) ($leg['account_id'] ?? 0);
                $account = $accountId ? Account::query()->find($accountId) : null;

                if (! $account) {
                    $this->addError($field.'account_id', 'Select an account.');
                } elseif (! $account->is_active) {
                    $this->addError($field.'account_id', 'Account is inactive.');
                }
            } elseif (($leg['account_source'] ?? '') === 'runtime') {
                $slot = $leg['runtime_slot'] ?? '';

                if ($slot === '' || ! in_array($slot, $slots, true)) {
                    $this->addError($field.'runtime_slot', 'Select a valid runtime slot for this event.');
                }
            } else {
                $this->addError($field.'account_source', 'Choose an account source.');
            }
        }

        if (! $hasDebit || ! $hasCredit) {
            $this->addError('legs', 'A recipe needs at least one debit and one credit leg.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Please fix the highlighted issues.']);

            return;
        }

        // --- persist ---------------------------------------------------------
        DB::transaction(function () use ($event): void {
            $event->update(['is_active' => $this->isActive]);
            $event->rules()->delete();

            foreach (array_values($this->legs) as $i => $leg) {
                $isFixed = ($leg['account_source'] ?? '') === 'fixed';

                $event->rules()->create([
                    'leg'            => $leg['leg'],
                    'account_source' => $leg['account_source'],
                    'account_id'     => $isFixed ? (int) $leg['account_id'] : null,
                    'runtime_slot'   => $isFixed ? null : ($leg['runtime_slot'] ?: null),
                    'amount_source'  => 'full',
                    'sort_order'     => $i,
                    'description'    => $leg['description'] ?: null,
                ]);
            }
        });

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Accounting rule updated.']);
        $this->selectEvent($event->id);
    }

    /** Live balance state for the editor preview. */
    public function getRecipeBalancedProperty(): bool
    {
        $hasDebit = collect($this->legs)->contains(fn ($l): bool => ($l['leg'] ?? '') === 'debit');
        $hasCredit = collect($this->legs)->contains(fn ($l): bool => ($l['leg'] ?? '') === 'credit');

        return $hasDebit && $hasCredit;
    }

    public function render(): View
    {
        $events = AccountingEvent::query()
            ->with(['rules.account:id,name,code,group'])
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        $editingEvent = $this->editingId
            ? AccountingEvent::query()->find($this->editingId)
            : null;

        $slots = $editingEvent ? AccountingEventRegistry::slots($editingEvent->key) : [];

        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('group')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'group']);

        return view('livewire.admin.accounts.settings.accounting-settings', [
            'eventsByModule' => $events,
            'editingEvent'   => $editingEvent,
            'slots'          => $slots,
            'accounts'       => $accounts,
        ])->layout('layouts.admin.admin');
    }
}
