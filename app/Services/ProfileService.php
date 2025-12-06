<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Repositories\Auth\UserRepository;
use App\Services\Contracts\ProfileServiceInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class ProfileService implements ProfileServiceInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ){}

    public function paginateCitizens(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $paginator = $this->userRepository->paginateCitizens($perPage, $page);

        Carbon::setLocale('ar');

        $paginator->getCollection()->transform(function ($user) {

            $lastLoginHuman = $user->last_login_at
                ? Carbon::parse($user->last_login_at)->diffForHumans()
                : 'لم يسجل الدخول بعد';

            return [
                'id'              => $user->id,
                'name'            => $user->name,
                'national_number' => $user->citizenProfile?->national_number,
                'email'           => $user->email,
                'last_login_human'=> $lastLoginHuman,
                'is_active'       => (bool) $user->is_active,
            ];
        });

        return $paginator;
    }

    public function paginateOfficers(int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $paginator = $this->userRepository->paginateOfficers($perPage, $page);

        Carbon::setLocale('ar');

        $paginator->getCollection()->transform(function ($user) {

            $lastLoginHuman = $user->last_login_at
                ? Carbon::parse($user->last_login_at)->diffForHumans()
                : 'لم يسجل الدخول بعد';

            return [
                'id'               => $user->id,
                'name'             => $user->name,
                'agency_name'      => $user->staffProfile->agency?->name,
                'email'            => $user->email,
                'last_login_human' => $lastLoginHuman,
                'is_active'        => (bool) $user->is_active,
            ];
        });

        return $paginator;
    }

    public function deactivateUser(int $userId): void
    {
        $user = $this->userRepository->findById($userId);
        if(!$user)
        {
            throw new ApiException("المستخدم الذي تحاول الوصول اليه غير موجود");
        }

        if(!$user->is_active)
        {
            throw new ApiException("حساب المستخدم معطل بالفعل");
        }

        $this->userRepository->setActiveStatus($user, false);
    }

    public function activateUser(int $userId): void
    {
        $user = $this->userRepository->findById($userId);

        if(!$user)
        {
            throw new ApiException("المستخدم الذي تحاول الوصول اليه غير موجود");
        }

        if($user->is_active == 1)
        {
            throw new ApiException("حساب المستخدم مفعل بالفعل");
        }

        $this->userRepository->setActiveStatus($user, true);
    }

    public function createOfficer(array $data): User
    {
        if($this->userRepository->emailExists($data['email']))
        {
            throw new ApiException("يوجد حساب مستخدم بهذا البريد الإلكتروني مسبقًا" , 422);
        }

        $userData = [
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'email_verified_at' => now(),
            'role'          => UserRole::OFFICER->value,
            'last_login_at' => null,
            'is_active'     => true,
        ];

        $profileData = [
            'agency_id' => $data['agency_id'],
        ];

        return $this->userRepository->createOfficerWithProfile($userData, $profileData);
    }
}
