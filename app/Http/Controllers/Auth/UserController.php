<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
}
