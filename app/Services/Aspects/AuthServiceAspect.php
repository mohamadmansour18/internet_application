<?php

namespace App\Services\Aspects;

use App\Exceptions\ApiException;
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

    /**
     * @throws \Throwable
     */
    public function registerCitizen(array $data): array
    {
        return $this->around(
            action : 'auth.register_citizen',
            context : [
                'name' => $data['name'],
                'email' => $data['email'],
                'national_number'=>$data['national_number'],
            ],
            Before: function () {
                if(Auth::check())
                {
                    throw new ApiException('حسابك اساسا موجود في النظام لايمكنك انشاء الحساب مرة ثانية' , 422);
                }
            },
            callback: fn() => $this->inner->registerCitizen($data),

            after: function(array $result){
                SendOtpCode::dispatch(email:  $result['user']->email , name:  $result['user']->name , code:  $result['otp']->code , purpose: $result['otp']->purpose);
            },
            audit: function (array $result){
                return [
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
            withLogging: true,
            withTiming: true
        );
    }
}
