<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Storage;
use Illuminate\Support\Facades\URL;

class LiveStream extends Model
{
	use HasFactory;

	public function getHlsUrl()
	{
		$path = URL::temporarySignedRoute(
            'storage.file',
            now()->addMinutes(30),
            ['file' => "live-hls/{$this->stream_id}/index.m3u8", 'user_id' => auth()->id()]
        );
		return url($path);
	}

	public function getStreamServer()
	{
		$proto = 'rtmp://';
		$host = config('livestreaming.server.host');
		$port = ':' . config('livestreaming.server.port');
		$path = '/' . config('livestreaming.server.path');
		return $proto . $host . $port . $path;
	}

	public function getStreamKeyUrl()
	{
		$path = $this->getStreamServer() . '?';
		$query = http_build_query([
			'name' => $this->stream_key,
		]);
		return $path . $query;
	}

	public function getStreamRtmpUrl()
	{
		return $this->getStreamServer() . '/' . $this->stream_id;
	}
}
