<?php

namespace App\Services\Aspects;

use App\Exceptions\ApiException;
use App\Helpers\TextHelper;
use App\Jobs\SendOtpCode;

use App\Models\User;
use App\Services\Contracts\AuthServiceInterface;
use App\Traits\AspectTrait;
use Illuminate\Support\Facades\Auth;

class AuthServiceAspect implements AuthServiceInterface
{
    use AspectTrait;

    public function __construct(
        protected AuthServiceInterface $inner
    )
    {}

    public function registerCitizen(array $data): array
    {
        return $this->around(
            action: 'auth.register_citizen',
            context: [
                'name' => $data['name'],
                'email' => $data['email'],
                'national_number'=>$data['national_number'],
                'time' => now()->format('Y-m-d H:i:s'),
            ],
            before: function () {
                if(Auth::check())
                {
                    throw new ApiException('حسابك اساسا موجود في النظام لايمكنك انشاء الحساب مرة ثانية' , 422);
                }
            },
            callback: fn() => $this->inner->registerCitizen($data),

            after: function(array $result){
                SendOtpCode::dispatch(email:  $result['user']->email , name:  $result['user']->name , code:  $result['otp']->otp_code , purpose: $result['otp']->purpose->value);
            },
            audit: function (array $result){
                return [
                    'actor_id' => $result['user']->id,
                    'subject_type' => User::class,
                    'subject_id'   => $result['user']->id,
                    'changes' => [
                        'action' => 'register_citizen',
                        'name' => $result['user']->name,
                        'email' => $result['user']->email,
                        'national_number' => $result['user']->national_number,
                    ]
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

    public function loginCitizen(array $data): array
    {
        $result = $this->around(
            action: 'auth.login_citizen',
            context: [
                'email' => $data['email'] ?? null,
                'ip' => $data['ip'] ?? null,
                'time' => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn() => $this->inner->loginCitizen($data),
            audit: function(array $result){
                return [
                    'actor_id' => $result['user']->id,
                    'subject_type' => User::class,
                    'subject_id'   => $result['user']->id,
                    'changes' => [
                        'action' => 'login',
                        'ip' => $data['ip'] ?? null,
                    ]
                ];
            },
            withTiming: true,
            withLogging: true,
        );

        return [
            'token' => $result['token'],
            'name' => TextHelper::fixBidi("مرحبا صديقي المواطن {$result['user']->name}")
        ];
    }

    public function verifyRegistration(array $data): array
    {
        return $this->around(
            action: 'auth.verify_citizen_registration',
            context: [
                'email' => $data['email'] ?? null,
                'time' => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn() => $this->inner->verifyRegistration($data),
            audit: function(array $result){
                return [
                    'actor_id' => $result['user']->id,
                    'subject_type' => User::class,
                    'subject_id'   => $result['user']->id,
                    'changes' => [
                        'action' => 'verify_registration',
                        'email' => $data['email'] ?? null,
                        'otp' => $result['otp'] ?? null,
                    ]
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

    public function resendOtp(string $email , string $purpose): array
    {
        return $this->around(
            action: 'auth.resend_otp',
            context: [
                'email' => $email ,
                'purpose' => $purpose,
                'time' => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn() => $this->inner->resendOtp($email , $purpose),
            after: function(array $result){
                SendOtpCode::dispatch(email: $result['user']->email , name:  $result['user']->name , code:  $result['otp']->otp_code , purpose: $result['otp']->purpose->value);
            },
            audit: function(array $result){
                return [
                    'actor_id' => $result['user']->id,
                    'subject_type' => User::class,
                    'subject_id'   => $result['user']->id,
                    'changes' => [
                        'action' => 'resend_Otp',
                        'purpose' => $result['otp']->purpose->value,
                        'email' => $email ?? null,
                        'otp' => $result['otp'] ?? null,
                    ]
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

    ////////////////////////////////////////////////////////////

    public function forgotPassword(string $email): array
    {
         return $this->around(
             action: 'auth.forgot_citizen_password',
             context: [
                 'email' => $email ,
                 'time' => now()->format('Y-m-d H:i:s'),
             ],
             callback: fn() => $this->inner->forgotPassword($email),
             after: function(array $result){
                 SendOtpCode::dispatch(email: $result['user']->email , name:  $result['user']->name , code:  $result['otp']->otp_code , purpose: $result['otp']->purpose->value);
             },
             audit: function (array $result){
                 return [
                     'actor_id' => $result['user']->id,
                     'subject_type' => User::class,
                     'subject_id'   => $result['user']->id,
                     'changes' => [
                         'action' => 'forgot_citizen_password',
                         'email' => $email ?? null,
                         'otp' => $result['otp'] ?? null,
                     ]
                 ];
             },
             withTiming: true,
             withLogging: true
         );
    }

    public function verifyForgotPasswordEmail(array $data): array
    {
        return $this->around(
            action: 'auth.verify_forgot_password_email',
            context: [
                'email' => $data['email'] ?? null,
                'otp' => $data['otp'] ?? null,
                'time' => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn() => $this->inner->verifyForgotPasswordEmail($data),
            audit: function(array $result){
                return [
                    'actor_id' => $result['user']->id,
                    'subject_type' => User::class,
                    'subject_id'   => $result['user']->id,
                    'changes' => [
                        'action' => 'verify_forgot_password_email_citizen',
                        'email' => $data['email'] ?? null,
                        'otp' => $result['otp'] ?? null,
                    ]
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }

    public function resetPassword(array $data):array
    {
        return $this->around(
            action: 'auth.citizen_reset_password',
            context: [
                'email' => $data['email'] ?? null,
                'time' => now()->format('Y-m-d H:i:s'),
            ],
            callback: fn() => $this->inner->resetPassword($data),
            audit: function(array $result){
                return [
                    'actor_id' => $result['user']->id,
                    'subject_type' => User::class,
                    'subject_id'   => $result['user']->id,
                    'changes' => [
                        'action' => 'citizen_reset_password',
                        'email' => $data['email'] ?? null,
                    ]
                ];
            },
            withTiming: true,
            withLogging: true,
        );
    }
}
