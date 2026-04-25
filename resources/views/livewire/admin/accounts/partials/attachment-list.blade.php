@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\TransactionAttachment> $attachments */
    $attachments = $attachments ?? collect();
    $fancyboxGroup = $fancyboxGroup ?? 'accounts-attachments';
    $canRemove = $canRemove ?? false;
    $removeMethod = $removeMethod ?? null;
    $emptyMessage = $emptyMessage ?? 'No attachments found.';
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
@endphp

<div class="space-y-3">
    @forelse ($attachments as $attachment)
        @php
            $file = $attachment->file;
            $extension = strtolower((string) ($file?->extension ?? ''));
            $isImage = in_array($extension, $imageExtensions, true);
            $isPdf = $extension === 'pdf';
            $canPreview = $isImage || $isPdf;
        @endphp
        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="h-10 w-10 shrink-0 rounded-lg bg-indigo-50 text-indigo-600 grid place-content-center text-sm font-semibold">
                    {{ strtoupper(substr($extension ?: 'file', 0, 3)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ $file?->name ?? ('Attachment #'.$attachment->id) }}</p>
                    <p class="text-xs text-gray-500">{{ strtoupper($extension ?: 'N/A') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @can('accounts.transaction-attachment.view')
                    @if ($canPreview && $file)
                        <a
                            href="{{ file_path($file->id) }}"
                            data-fancybox="{{ $fancyboxGroup }}"
                            @if ($isPdf) data-type="iframe" data-autosize="true" @endif
                            data-caption="{{ $file->name ?? ('Attachment #'.$attachment->id) }}"
                            class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50"
                        >
                            View
                        </a>
                    @endif

                    @if ($file)
                        <a
                            href="{{ route('admin.accounts.transactions.attachments.download', ['transaction' => $attachment->transaction_id, 'file' => $file->id]) }}"
                            class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium text-gray-700 ring-1 ring-gray-200 transition hover:bg-gray-50"
                        >
                            Download
                        </a>
                    @endif
                @endcan

                @if ($canRemove && $removeMethod)
                    <button
                        type="button"
                        wire:click="{{ $removeMethod }}({{ $attachment->id }})"
                        class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium text-rose-600 ring-1 ring-rose-200 transition hover:bg-rose-50"
                    >
                        Remove
                    </button>
                @endif
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500">{{ $emptyMessage }}</p>
    @endforelse
</div>
