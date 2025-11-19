<?php

namespace App\Services;

use App\Enums\OtpCodePurpose;
use App\Enums\UserRole;
use App\Jobs\SendOtpCode;
use App\Models\OtpCodes;
use App\Models\User;
use App\Repositories\Auth\OtpCodesRepository;
use App\Repositories\Auth\UserRepository;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\Hash;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly OtpCodesRepository $otpCodesRepository
    ){}

    public function registerCitizen(array $data): array
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::CITIZEN->value ,
            'last_login_at' => null ,
            'is_active' => false
        ];

        $profileData = [
            'national_number' => $data['national_number'],
        ];

        $user = $this->userRepository->createCitizenWithProfile($userData, $profileData);

        $otp = $this->otpCodesRepository->createOtpFor($user->id , OtpCodePurpose::Verification->value);

        return [
            'user' => $user,
            'otp' => $otp,
        ];
    }
}
