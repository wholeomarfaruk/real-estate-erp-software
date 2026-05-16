<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Customer;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerList extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterType   = 'all';
    public string $filterKyc    = 'all';
    public string $filterStatus = 'all';
    public bool   $drawerOpen   = false;
    public ?int   $editingId    = null;
    public string $activeTab    = 'identity';

    // Drawer fields
    public string $dType          = 'individual';
    public string $dName          = '';
    public string $dFatherName    = '';
    public string $dMotherName    = '';
    public string $dDob           = '';
    public string $dGender        = '';
    public string $dPhone         = '';
    public string $dPhoneAlt      = '';
    public string $dEmail         = '';
    public string $dAddress       = '';
    public string $dDistrict      = '';
    public string $dDivision      = '';
    public string $dPostalCode    = '';
    public string $dCompanyName   = '';
    public string $dCompanyRegNo  = '';
    public string $dCompanyTaxId  = '';
    public string $dDocType       = '';
    public string $dDocNo         = '';
    public string $dDocIssueDate  = '';
    public string $dDocExpiryDate = '';
    public string $dKycStatus     = 'pending';
    public string $dKycDate       = '';
    public string $dStatus        = 'active';
    public string $dSource        = '';
    public string $dNotes         = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('customer.view'), 403);
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->can('customer.create'), 403);

        $this->editingId    = null;
        $this->activeTab    = 'identity';
        $this->dType        = 'individual';
        $this->dName        = '';
        $this->dFatherName  = '';
        $this->dMotherName  = '';
        $this->dDob         = '';
        $this->dGender      = '';
        $this->dPhone       = '';
        $this->dPhoneAlt    = '';
        $this->dEmail       = '';
        $this->dAddress     = '';
        $this->dDistrict    = '';
        $this->dDivision    = '';
        $this->dPostalCode  = '';
        $this->dCompanyName   = '';
        $this->dCompanyRegNo  = '';
        $this->dCompanyTaxId  = '';
        $this->dDocType       = '';
        $this->dDocNo         = '';
        $this->dDocIssueDate  = '';
        $this->dDocExpiryDate = '';
        $this->dKycStatus     = 'pending';
        $this->dKycDate       = '';
        $this->dStatus        = 'active';
        $this->dSource        = '';
        $this->dNotes         = '';
        $this->drawerOpen     = true;
    }

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->can('customer.edit'), 403);

        $customer = Customer::findOrFail($id);

        $this->editingId      = $customer->id;
        $this->activeTab      = 'identity';
        $this->dType          = $customer->type ?? 'individual';
        $this->dName          = $customer->name ?? '';
        $this->dFatherName    = $customer->father_name ?? '';
        $this->dMotherName    = $customer->mother_name ?? '';
        $this->dDob           = $customer->date_of_birth?->format('Y-m-d') ?? '';
        $this->dGender        = $customer->gender ?? '';
        $this->dPhone         = $customer->phone ?? '';
        $this->dPhoneAlt      = $customer->phone_alt ?? '';
        $this->dEmail         = $customer->email ?? '';
        $this->dAddress       = $customer->address ?? '';
        $this->dDistrict      = $customer->district ?? '';
        $this->dDivision      = $customer->division ?? '';
        $this->dPostalCode    = $customer->postal_code ?? '';
        $this->dCompanyName   = $customer->company_name ?? '';
        $this->dCompanyRegNo  = $customer->company_registration_no ?? '';
        $this->dCompanyTaxId  = $customer->company_tax_id ?? '';
        $this->dDocType       = $customer->doc_type ?? '';
        $this->dDocNo         = $customer->doc_no ?? '';
        $this->dDocIssueDate  = $customer->doc_issue_date?->format('Y-m-d') ?? '';
        $this->dDocExpiryDate = $customer->doc_expiry_date?->format('Y-m-d') ?? '';
        $this->dKycStatus     = $customer->kyc_status ?? 'pending';
        $this->dKycDate       = $customer->kyc_date?->format('Y-m-d') ?? '';
        $this->dStatus        = $customer->status ?? 'active';
        $this->dSource        = $customer->source ?? '';
        $this->dNotes         = $customer->notes ?? '';
        $this->drawerOpen     = true;
    }

    public function saveCustomer(): void
    {
        $this->validate([
            'dName'  => 'required|string|max:255',
            'dPhone' => 'required|string|max:50',
            'dType'  => 'required|in:individual,company',
        ], [
            'dName.required'  => 'Customer name is required.',
            'dPhone.required' => 'Phone number is required.',
            'dType.required'  => 'Customer type is required.',
        ]);

        $data = [
            'type'                   => $this->dType,
            'name'                   => $this->dName,
            'father_name'            => $this->dFatherName ?: null,
            'mother_name'            => $this->dMotherName ?: null,
            'date_of_birth'          => $this->dDob ?: null,
            'gender'                 => $this->dGender ?: null,
            'phone'                  => $this->dPhone,
            'phone_alt'              => $this->dPhoneAlt ?: null,
            'email'                  => $this->dEmail ?: null,
            'address'                => $this->dAddress ?: null,
            'district'               => $this->dDistrict ?: null,
            'division'               => $this->dDivision ?: null,
            'postal_code'            => $this->dPostalCode ?: null,
            'company_name'           => $this->dCompanyName ?: null,
            'company_registration_no'=> $this->dCompanyRegNo ?: null,
            'company_tax_id'         => $this->dCompanyTaxId ?: null,
            'doc_type'               => $this->dDocType ?: null,
            'doc_no'                 => $this->dDocNo ?: null,
            'doc_issue_date'         => $this->dDocIssueDate ?: null,
            'doc_expiry_date'        => $this->dDocExpiryDate ?: null,
            'kyc_status'             => $this->dKycStatus ?: 'pending',
            'kyc_date'               => $this->dKycDate ?: null,
            'status'                 => $this->dStatus ?: 'active',
            'source'                 => $this->dSource ?: null,
            'notes'                  => $this->dNotes ?: null,
        ];

        if ($this->editingId) {
            abort_unless(auth()->user()?->can('customer.edit'), 403);

            $data['updated_by'] = auth()->id();
            Customer::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer updated successfully.']);
        } else {
            abort_unless(auth()->user()?->can('customer.create'), 403);

            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();
            Customer::create($data);
            $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer created successfully.']);
        }

        $this->closeDrawer();
        $this->resetPage();
    }

    public function deleteCustomer(int $id): void
    {
        abort_unless(auth()->user()?->can('customer.delete'), 403);

        $customer = Customer::find($id);
        if (! $customer) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Customer not found.']);
            return;
        }

        $customer->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Customer deleted successfully.']);
        $this->resetPage();
    }

    public function closeDrawer(): void
    {
        $this->drawerOpen = false;
        $this->editingId  = null;
        $this->resetValidation();
    }

    public function render()
    {
        abort_unless(auth()->user()?->can('customer.view'), 403);

        $query = Customer::query()
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                $q->where(function ($inner) use ($s) {
                    $inner->where('name', 'like', $s)
                          ->orWhere('customer_id', 'like', $s)
                          ->orWhere('phone', 'like', $s)
                          ->orWhere('email', 'like', $s)
                          ->orWhere('doc_no', 'like', $s);
                });
            })
            ->when($this->filterType !== 'all', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->filterKyc  !== 'all', fn ($q) => $q->where('kyc_status', $this->filterKyc))
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderBy('created_at', 'desc');

        $customers = $query->paginate(15);

        $kpi = [
            'total'    => Customer::count(),
            'verified' => Customer::where('kyc_status', 'verified')->count(),
            'pending'  => Customer::where('kyc_status', 'pending')->count(),
            'active'   => Customer::where('status', 'active')->count(),
            'inactive' => Customer::whereIn('status', ['inactive', 'suspended'])->count(),
        ];

        return view('livewire.admin.customers.customer-list', compact('customers', 'kpi'))
            ->layout('layouts.admin.admin');
    }
}
