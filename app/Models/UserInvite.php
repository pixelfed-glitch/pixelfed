<?php

namespace App\Models;

use App\Profile;
use Illuminate\Database\Eloquent\Model;

class UserInvite extends Model
{
	public function sender()
	{
		return $this->profile_id;
	}

    public function url()
    {
        return url('/auth/invite/u/' . $this->token);
    }
}
