<?php
//Helpers/Helpers.php
use App\Models\File;

if (!function_exists('file_path')) {
    function file_path($id, $type = 'original')
    {
        $file = File::with('items')->find($id);

        if (!$file) {
            return null;
        }

        $item = $file->items->firstWhere('type', $type);

        return $item ? asset('storage/' . $item->path) : null;
    }
}
