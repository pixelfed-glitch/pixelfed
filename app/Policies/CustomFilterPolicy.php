<?php

namespace App\Policies;

use App\Models\CustomFilter;
use App\Models\User;

class CustomFilterPolicy
{
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
