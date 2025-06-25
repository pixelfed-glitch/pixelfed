<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;
use Illuminate\Support\Facades\URL;

class GroupMedia extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'json',
            'processed_at' => 'datetime',
            'thumbnail_generated' => 'datetime'
        ];
    }

    public function url()
    {
        if($this->cdn_url) {
            return $this->cdn_url;
        }
        return url(URL::temporarySignedRoute(
            'storage.file',
            now()->addMinutes(30),
            ['file' => $this->media_path, 'user_id' => auth()->id()]
        ));
    }

    public function thumbnailUrl()
    {
        return url(URL::temporarySignedRoute(
            'storage.file',
            now()->addMinutes(30),
            ['file' => $this->thumbnail_url, 'user_id' => auth()->id()]
        ));
    }
}
