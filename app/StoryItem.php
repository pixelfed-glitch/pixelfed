<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Pixelfed\Snowflake\HasSnowflakePrimary;
use Storage;
use Illuminate\Support\Facades\URL;

class StoryItem extends Model
{
	use HasSnowflakePrimary;

	/**
	* Indicates if the IDs are auto-incrementing.
	*
	* @var bool
	*/
	public $incrementing = false;

	/**
	* The attributes that should be mutated to dates.
	*
	* @var array
	*/
	protected $casts = [
		'expires_at' => 'datetime'
	];

	protected $visible = ['id'];

	public function story()
	{
		return $this->belongsTo(Story::class);
	}

	public function url()
	{
		return url(URL::temporarySignedRoute(
            'storage.file',
            now()->addMinutes(30),
            ['file' => preg_replace('#^public/#','/',$this->media_path), 'user_id' => auth()->id()]
        ));
	}
}
