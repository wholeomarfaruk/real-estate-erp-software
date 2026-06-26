<?php

namespace App\Livewire\Admin\Accounts\Entry;

use App\Livewire\Admin\Accounts\Concerns\InteractsWithAccountsAccess;
use App\Livewire\Traits\WithMediaPicker;
use App\Models\AccountEntryType;
use App\Services\Accounts\Entry\EntrySubmissionService;
use Livewire\Component;

abstract class BaseEntryForm extends Component
{
    use InteractsWithAccountsAccess, WithMediaPicker;

    public AccountEntryType $entryType;

    public ?int $debit_account_id = null;
    public ?int $credit_account_id = null;
    public string $amount = '';
    public string $date = '';
    public string $method = 'cash';
    public string $reference_no = '';
    public string $name = '';
    public string $phone = '';
    public string $notes = '';
    public array $mediaIds = [];

    abstract protected function extraRules(): array;
    abstract protected function buildPayload(): array;

    protected function baseRules(): array
    {
        return [
            'debit_account_id'  => 'required|integer|exists:accounts,id',
            'credit_account_id' => 'required|integer|exists:accounts,id',
            'amount'   => 'required|numeric|gt:0',
            'date'     => 'required|date',
            'method'   => 'required|string',
            'notes'    => 'nullable|string|max:1000',
            'mediaIds' => 'nullable|array',
            'mediaIds.*' => 'integer|exists:files,id',
        ];
    }

    public function save(): void
    {
        try {
            $this->authorizePermission($this->entryType->permission);

            $rules = array_merge($this->baseRules(), $this->extraRules());
            $this->validate($rules);

            $payload = $this->buildPayload();
            $payload['attachments'] = $this->normalizedAttachmentIds();

            app(EntrySubmissionService::class)->submit($this->entryType, $payload);

            $this->dispatch('toast', type: 'success', message: $this->entryType->name . ' submitted successfully.');
            $this->redirectRoute('admin.account-entries.index', navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('toast', type: 'error', message: $e->validator->errors()->first());
            throw $e;
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function removeAttachment(int $index): void
    {
        unset($this->mediaIds[$index]);
        $this->mediaIds = array_values($this->mediaIds);
    }

    protected function normalizedAttachmentIds(): array
    {
        return array_values(
            array_unique(
                array_filter(
                    array_map('intval', $this->mediaIds),
                    fn ($id) => $id > 0
                )
            )
        );
    }
}
