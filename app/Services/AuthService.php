<?php

namespace App\Services;

use App\Enums\OtpCodePurpose;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Jobs\FailedLogin;
use App\Jobs\SendOtpCode;
use App\Models\OtpCodes;
use App\Models\User;
use App\Repositories\Auth\FailedLoginRepository;
use App\Repositories\Auth\OtpCodesRepository;
use App\Repositories\Auth\UserRepository;
use App\Services\Contracts\AuthServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly OtpCodesRepository $otpCodesRepository,
        private readonly FailedLoginRepository $failedLoginRepository,
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

    public function loginCitizen(array $data): array
    {

        $user = $this->userRepository->findByEmail($data['email']);

        if(!$user)
        {
            throw new ApiException('بيانات الدخول غير صحيحة' , 422);
        }

        $maxAttempts  = 3;
        $decayMinutes = 5;


        if(!Hash::check($data['password'] , $user->password))
        {
            $this->failedLoginRepository->recordFailedLogin($user , $data['ip'] , $data['user_agent']);

            $recentFails = $this->failedLoginRepository->countRecentFailedLogins($user , $decayMinutes);

            if($recentFails >= $maxAttempts)
            {
                FailedLogin::dispatch($user , [
                    'ip_address'  => $data['ip'],
                    'user_agent'  => $data['user_agent'],
                    'occurred_at' => now()->toDateTimeString(),
                ]);
            }

            throw new ApiException('بيانات الدخول غير صحيحة' , 422);
        }

        if(is_null($user->email_verified_at))
        {
            throw new ApiException('يجب ان تقوم بتأكيد الحساب قبل القيام بعملية تسجيل الدخول' , 422);
        }

        DB::transaction(function () use ($user){
            $this->failedLoginRepository->clearFailedLogins($user);
            $this->userRepository->updateLastLoginAt($user);
        });

        $token = JWTAuth::fromUser($user);

        return [
            'token' => $token,
            'user' => $user
        ];
    }
}
