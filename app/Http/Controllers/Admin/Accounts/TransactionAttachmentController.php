<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionAttachmentController extends Controller
{
    public function download(Transaction $transaction, File $file): StreamedResponse
    {
        abort_unless(auth()->user()?->can('accounts.transaction-attachment.view'), 403, 'Unauthorized action.');

        $isAttached = $transaction->attachments()->where('file_id', $file->id)->exists();

        abort_unless($isAttached, 404, 'Attachment not found.');

        $item = $file->items()->where('type', 'original')->first();

        abort_unless($item && Storage::disk('public')->exists($item->path), 404, 'Attachment file not found.');

        $baseName = trim((string) ($file->name ?: ('attachment-'.$file->id)));
        $extension = strtolower((string) ($file->extension ?: pathinfo($item->path, PATHINFO_EXTENSION)));
        $downloadName = $baseName;

        if ($extension !== '' && ! str_ends_with(strtolower($baseName), '.'.$extension)) {
            $downloadName .= '.'.$extension;
        }

        return Storage::disk('public')->download($item->path, $downloadName);
    }
}
