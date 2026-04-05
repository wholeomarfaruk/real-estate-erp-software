<?php

namespace App\Livewire\Admin\Supplier\Supplier;

use App\Livewire\Admin\Supplier\Concerns\InteractsWithSupplierAccess;
use App\Models\Supplier;
use App\Services\Supplier\SupplierLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SupplierForm extends Component
{
    use InteractsWithSupplierAccess;

    public ?Supplier $supplierRecord = null;

    public ?int $supplierId = null;

    public bool $editMode = false;

    public string $name = '';

    public string $code = '';

    public ?string $company_name = null;

    public ?string $contact_person = null;

    public ?string $phone = null;

    public ?string $alternate_phone = null;

    public ?string $email = null;

    public ?string $address = null;

    public ?string $trade_license_no = null;

    public ?string $tin_no = null;

    public ?string $bin_no = null;

    public float|int|string $opening_balance = 0;

    public string $opening_balance_type = Supplier::OPENING_BALANCE_TYPE_PAYABLE;

    public int|string $payment_terms_days = 0;

    public float|int|string $credit_limit = 0;

    public ?string $notes = null;

    public bool|string|int $status = '1';

    public function mount(?Supplier $supplier = null): void
    {
        if ($supplier && $supplier->exists) {
            $this->authorizePermission('supplier.edit');

            $this->editMode = true;
            $this->supplierRecord = $supplier;
            $this->supplierId = $supplier->id;
            $this->name = $supplier->name;
            $this->code = $supplier->code ?: $this->generateSupplierCode();
            $this->company_name = $supplier->company_name;
            $this->contact_person = $supplier->contact_person;
            $this->phone = $supplier->phone;
            $this->alternate_phone = $supplier->alternate_phone;
            $this->email = $supplier->email;
            $this->address = $supplier->address;
            $this->trade_license_no = $supplier->trade_license_no;
            $this->tin_no = $supplier->tin_no;
            $this->bin_no = $supplier->bin_no;
            $this->opening_balance = (float) $supplier->opening_balance;
            $this->opening_balance_type = $supplier->opening_balance_type ?: Supplier::OPENING_BALANCE_TYPE_PAYABLE;
            $this->payment_terms_days = (int) ($supplier->payment_terms_days ?? 0);
            $this->credit_limit = (float) ($supplier->credit_limit ?? 0);
            $this->notes = $supplier->notes;
            $this->status = $supplier->status ? '1' : '0';

            return;
        }

        $this->authorizePermission('supplier.create');
        $this->code = $this->generateSupplierCode();
    }

    public function save()
    {
        if ($this->editMode) {
            $this->authorizePermission('supplier.edit');
        } else {
            $this->authorizePermission('supplier.create');
        }

        $validated = $this->validate($this->rules(), $this->messages());
        $validated['status'] = (bool) $validated['status'];
        $validated['updated_by'] = auth()->id();

        DB::transaction(function () use ($validated): void {
            $savedSupplier = null;

            if ($this->editMode && $this->supplierRecord) {
                $this->supplierRecord->update($validated);
                $savedSupplier = $this->supplierRecord->fresh();

            } else {
                $savedSupplier = Supplier::query()->create([
                    ...$validated,
                    'created_by' => auth()->id(),
                ]);
            }

            if ($savedSupplier) {
                app(SupplierLedgerService::class)->postOpeningBalance($savedSupplier, (int) auth()->id(), false);
            }
        });

        $this->dispatch('toast', [
            'type' => 'success',
            'message' => $this->editMode ? 'Supplier updated successfully.' : 'Supplier created successfully.',
        ]);

        return redirect()->route('admin.supplier.suppliers.index');
    }

    public function render(): View
    {
        if ($this->editMode) {
            $this->authorizePermission('supplier.edit');
        } else {
            $this->authorizePermission('supplier.create');
        }

        return view('livewire.admin.supplier.supplier.supplier-form', [
            'openingBalanceTypes' => [
                Supplier::OPENING_BALANCE_TYPE_PAYABLE => 'Payable',
                Supplier::OPENING_BALANCE_TYPE_ADVANCE => 'Advance',
            ],
        ])->layout('layouts.admin.admin');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', Rule::unique('suppliers', 'code')->ignore($this->supplierId)],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'alternate_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'trade_license_no' => ['nullable', 'string', 'max:100'],
            'tin_no' => ['nullable', 'string', 'max:100'],
            'bin_no' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'opening_balance_type' => ['required', Rule::in([Supplier::OPENING_BALANCE_TYPE_PAYABLE, Supplier::OPENING_BALANCE_TYPE_ADVANCE])],
            'payment_terms_days' => ['required', 'integer', 'min:0'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Supplier name is required.',
            'code.required' => 'Supplier code is required.',
            'code.unique' => 'This supplier code is already in use.',
            'phone.required' => 'Phone is required.',
        ];
    }

    protected function generateSupplierCode(): string
    {
        $counter = max(1, Supplier::query()->withTrashed()->count() + 1);

        do {
            $code = 'SUP-'.str_pad((string) $counter, 6, '0', STR_PAD_LEFT);
            $exists = Supplier::query()->withTrashed()->where('code', $code)->exists();
            $counter++;
        } while ($exists);

        return $code;
    }
}
