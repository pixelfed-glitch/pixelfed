<?php

namespace App\Policies;

use App\Models\CustomFilter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CustomFilterPolicy
{
    use HandlesAuthorization;

    public function view(User $user, CustomFilter $filter)
    {
        return $user->profile_id === $filter->profile_id;
    }

    public function update(User $user, CustomFilter $filter)
    {
        return $user->profile_id === $filter->profile_id;
    }

    public function delete(User $user, CustomFilter $filter)
    {
        return $user->profile_id === $filter->profile_id;
    }
}
