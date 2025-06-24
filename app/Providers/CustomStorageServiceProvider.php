<?php

namespace App\Providers;

use App\Http\Requests\GenerateFileToken;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;

class CustomStorageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Storage::extend('secure-public', function ($app, $config) {
            $adapter = Storage::disk('public')->getAdapter();
            return new class($adapter, $config) extends FilesystemAdapter {
                protected $adapter;
                protected $config;

                public function __construct($adapter, $config)
                {
                    $this->adapter = $adapter;
                    $this->config = $config;
                }

                public function url($path)
                {
                    return GenerateFileToken::generateFileUrl($path);
                }

                // Delegate other methods to the underlying adapter
                public function __call($method, $parameters)
                {
                    return call_user_func_array([$this->adapter, $method], $parameters);
                }
            };
        });
    }
}
