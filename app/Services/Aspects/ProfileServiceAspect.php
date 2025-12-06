<?php

namespace App\Services\Aspects;

use App\Models\User;
use App\Services\Contracts\ProfileServiceInterface;
use App\Traits\AspectTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ProfileServiceAspect implements ProfileServiceInterface
{
    use AspectTrait ;

    public function __construct(
        protected ProfileServiceInterface $inner
    ){}

    public function paginateCitizens(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->around(

            callback: fn () => $this->inner->paginateCitizens($perPage, $page),
            audit: function (LengthAwarePaginator $result) {
                return [
                    'actor_id'     => Auth::id(),
                    'subject_type' => User::class,
                    'subject_id'   =>  Auth::id(),
                    'changes'      => [
                        'action'       => 'view_citizens_list',
                        'returned'     => $result->count(),
                        'current_page' => $result->currentPage(),
                    ],
                ];
            },
        );
    }

    public function paginateOfficers(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->around(

            callback: fn () => $this->inner->paginateOfficers($perPage, $page),
            audit: function (LengthAwarePaginator $result) {
                return [
                    'actor_id'     => Auth::id(),
                    'subject_type' => User::class,
                    'subject_id'   =>  Auth::id(),
                    'changes'      => [
                        'action'       => 'view_officer_list',
                        'returned'     => $result->count(),
                        'current_page' => $result->currentPage(),
                    ],
                ];
            },
        );
    }

    public function deactivateUser(int $userId): void
    {
        $this->around(
            callback: fn () => $this->inner->deactivateUser($userId),
            audit: function () use ($userId) {
                return [
                    'actor_id'     => Auth::id(),
                    'subject_type' => User::class,
                    'subject_id'   => $userId,
                    'changes'      => [
                        'action'    => 'deactivate_user',
                        'is_active' => false,
                    ],
                ];
            },
        );
    }

    public function activateUser(int $userId): void
    {
        $this->around(
            callback: fn () => $this->inner->activateUser($userId),
            audit: function () use ($userId) {
                return [
                    'actor_id'     => Auth::id(),
                    'subject_type' => User::class,
                    'subject_id'   => $userId,
                    'changes'      => [
                        'action'    => 'deactivate_user',
                        'is_active' => false,
                    ],
                ];
            },
        );
    }

    public function createOfficer(array $data): User
    {
        return $this->around(
            callback: fn () => $this->inner->createOfficer($data),
            audit: function (User $user) {
                return [
                    'actor_id'     => Auth::id(),
                    'subject_type' => User::class,
                    'subject_id'   => $user->id,
                    'changes'      => [
                        'action'     => 'create_officer',
                        'role'       => $user->role,
                        'email'      => $user->email,
                    ],
                ];
            },
        );
    }
}
