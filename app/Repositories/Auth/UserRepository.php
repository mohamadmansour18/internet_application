<?php

namespace App\Repositories\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function createCitizenWithProfile(array $userData , array $profileData)
    {
        return DB::transaction(function() use($userData, $profileData){
            $user = User::query()->create($userData);
            $user->citizenProfile()->create($profileData);
            return $user->fresh('citizenProfile');
        });
    }
}
