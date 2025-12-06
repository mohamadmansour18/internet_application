<?php

namespace App\Repositories\Auth;

use App\Enums\UserRole;
use App\Models\FailedLogin;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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

    public function findByEmail(string $email): User
    {
        return User::where('email' , $email)->first();
    }

    public function updateLastLoginAt(User $user): void
    {
        $user->last_login_at = now();
        $user->save();
    }

    //--------------------<DASHBOARD>--------------------//

    public function findOfficerOrAdminByEmail(string $email): ?User
    {
        return User::where('email' , $email)
            ->whereIn('role' , [UserRole::OFFICER->value , UserRole::MANAGER->value])
            ->first();
    }

    public function paginateCitizens(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return User::query()
            ->with(['citizenProfile:id,user_id,national_number'])
            ->where('role', UserRole::CITIZEN->value)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function paginateOfficers(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return User::query()
            ->with(['staffProfile.agency:id,name'])
            ->where('role', UserRole::OFFICER->value)
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById(int $userId): User|Builder|null
    {
        return User::query()->find($userId);
    }

    public function setActiveStatus(User $user, bool $isActive): User
    {
        $user->is_active = $isActive;
        $user->save();

        return $user;
    }

    public function createOfficerWithProfile(array $userData , array $profileData)
    {
        return DB::transaction(function () use ($userData, $profileData){

            $user = User::query()->create($userData);

            $user->staffProfile()->create($profileData);

            return $user->fresh('staffProfile');
        });
    }

    public function emailExists(string $email): bool
    {
        return User::query()->where('email' , $email)->exists();
    }
}
