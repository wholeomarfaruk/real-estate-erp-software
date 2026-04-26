<?php

// Helpers/Helpers.php
use App\Models\File;

if (! function_exists('file_path')) {
    function file_path($id, $type = 'original')
    {
        $file = File::with('items')->find($id);

        if (! $file) {
            return null;
        }

        $item = $file->items->firstWhere('type', $type);

        return $item ? asset('storage/'.$item->path) : null;
    }
}

if (! function_exists('account_reference_config')) {
    /**
     * @return array<string, array<string, mixed>>
     */
    function account_reference_config(): array
    {
        $references = config('account_references');

        if (is_array($references) && $references !== []) {
            return $references;
        }

        $path = config_path('account_references.php');

        return file_exists($path) ? require $path : [];
    }
}
