<?php

namespace App\Filesystem;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class SignedUrlAdapter extends FilesystemAdapter
{
    public function url($path)
    {
        Log::info('Generating signed URL for path: ' . $path);
        // Generate a signed URL pointing to a route that serves the file
        return URL::signedRoute('storage.file', ['file' => $path]);
    }
}
