<?php

namespace App\Livewire\Admin\Accounts\Assets;

use App\Enums\Accounts\AccountSubType;
use App\Models\Account;
use App\Models\BankAccount;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class BankList extends Component
{
    use WithPagination;
    public string $search = '';



    public string $statusFilter = '';

    public bool $showFormModal = false;

    public ?int $editingId = null;

    public ?string $code = null;

    public string $bank_name;
    public string $ac_number;
    public string $holder_name;
    public string $branch;
    public string $route_code;
    public string $swift_code;
    public string $address;
    public string $note;
    public ?string $phone;
    public ?string $email;
    public ?int $account_id;

    public string $status = 'active';

    protected string $paginationTheme = 'tailwind';

    public function mount(){


    }

    public function render()
    {

           $accounts= BankAccount::query()

           ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                     $q->where('bank_name', 'like', "%{$search}%")
                        ->orWhere('ac_number', 'like', "%{$search}%");
                });
           })->when($this->statusFilter, function ($query, $status) {
                $query->where('status', $status);
           })
        //    ->withSum('account.transactionLines as balance', 'debit - credit')
        //     ->orderBy('bank_name')

           ->paginate(15);


            // dd($accounts);
            $totalBankBalance = 0;
            $accounts->each(function ($account) use (&$totalBankBalance) {
                $balance = $account->account ? $account->account->transactionLines()->sum('debit') - $account->account->transactionLines()->sum('credit') : 0;
                $account->balance = $balance;
                $totalBankBalance += $balance;
            });
            $assetAccounts = Account::query()->where('type', 'asset')->whereIn('sub_type', AccountSubType::assetTypes())->get();



        return view('livewire.admin.accounts.assets.bank-list', compact('accounts', 'totalBankBalance', 'assetAccounts'))->layout('layouts.admin.admin');
    }
    public function openCreateModal(): void
    {


        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {


        $account = BankAccount::query()->find($id);

        if (! $account) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account not found.']);

            return;
        }
        $this->editingId = $account->id;
        $this->code = $account->code;
        $this->bank_name = $account->bank_name;
        $this->ac_number = $account->ac_number;
        $this->holder_name = $account->holder_name;
        $this->branch = $account->branch;
        $this->route_code = $account->route_code;
        $this->swift_code = $account->swift_code;
        $this->address = $account->address;
        $this->note = $account->note;
        $this->status = $account->status;
        $this->account_id = $account->account_id;
        $this->phone = $account->phone;
        $this->email = $account->email;


        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
    }

    public function save(): void
    {


        $validated = $this->validate($this->rules(), $this->messages());

            BankAccount::query()->updateOrCreate(
                ['id' => $this->editingId],
                $validated
            );


        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account saved successfully.']);

        $this->showFormModal = false;
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {


        $account = BankAccount::query()->find($id);

        if (! $account) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Account not found.']);

            return;
        }

        $account->update([
            'status' => $account->status === 'active' ? 'inactive' : 'active',
        ]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account status updated successfully.']);
    }

    public function deleteAccount(int $id): void
    {

        $account = BankAccount::query()->find($id);

        $account->delete();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Account deleted successfully.']);
    }
    protected function rules(): array
    {
        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('bank_accounts', 'code')->ignore($this->editingId),
            ],
            'bank_name' => ['required', 'string', 'max:150'],
            'ac_number' => ['required', 'string', 'max:50'],
            'holder_name' => ['required', 'string', 'max:150'],
            'branch' => ['required', 'string', 'max:150'],
            'route_code' => ['nullable', 'string', 'max:50'],
            'swift_code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'account_id' => ['nullable', 'exists:accounts,id'],
             'phone' => ['nullable', 'string', 'max:20'],
             'email' => ['nullable', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'bank_name.required' => 'Bank name is required.',
            'ac_number.required' => 'Account number is required.',
            'holder_name.required' => 'Holder name is required.',
            'branch.required' => 'Branch is required.',
            'code.unique' => 'Account code must be unique.',
            'status.required' => 'Status is required.',
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'bank_name', 'ac_number', 'holder_name', 'branch', 'route_code', 'swift_code', 'address', 'note', 'status', 'account_id', 'phone', 'email']);
        $this->status= 'active';
    }
}
