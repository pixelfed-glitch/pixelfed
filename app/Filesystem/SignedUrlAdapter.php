<?php

namespace App\Filesystem;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\URL;

class SignedUrlAdapter extends FilesystemAdapter
{
    public function url($path)
    {
        // Generate a signed URL pointing to a route that serves the file
        return URL::signedRoute('storage.file', ['file' => $path]);
    }
}
