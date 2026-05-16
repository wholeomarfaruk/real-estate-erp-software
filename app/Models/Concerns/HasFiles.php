<?php

namespace App\Models\Concerns;

use App\Models\File;

trait HasFiles
{
    public function fileables()
    {
        return $this->morphMany(\App\Models\Fileable::class, 'fileable')->orderBy('sort_order');
    }

    public function attachFile(int $fileId, string $category = 'other', ?string $caption = null, bool $isCover = false): void
    {
        $isCover = $isCover || ! $this->fileables()->exists();

        $this->fileables()->create([
            'file_id'    => $fileId,
            'category'   => $category,
            'caption'    => $caption,
            'is_cover'   => $isCover,
            'sort_order' => $this->fileables()->max('sort_order') + 1,
        ]);
    }

    public function coverFile()
    {
        return $this->morphOne(\App\Models\Fileable::class, 'fileable')
            ->where('is_cover', true)
            ->with('file');
    }
}
