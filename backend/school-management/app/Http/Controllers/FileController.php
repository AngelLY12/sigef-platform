<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function download(string $disk, string $path)
    {
        abort_unless(
            in_array($disk, ['local', 'public']),
            404
        );
        return Storage::disk($disk)->response($path, null, [
            'Content-Disposition' => 'inline; filename="'.basename($path).'"',
        ]);
    }

}
