<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginCitizenRequest;
use App\Http\Requests\RegisterCitizenRequest;
use App\Services\Contracts\AuthServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthServiceInterface $authService,
    ){}

    public function registerCitizen(RegisterCitizenRequest $request): JsonResponse
    {
        $data = $this->authService->registerCitizen($request->validated());

        return $this->successResponse(
            message: "تم انشاء بريدك بنجاح ، الرجاء القيام بتأكيد بريدك الالكتروني" ,
            statusCode: 201
        );
    }

    public function loginCitizen(LoginCitizenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['ip'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        $result = $this->authService->loginCitizen($data);

        return $this->dataResponse(
            data: $result ,
            statusCode: 200
        );
    }
}
