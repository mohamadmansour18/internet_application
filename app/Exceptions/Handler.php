<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    use ApiResponse;
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (ApiException $e , $request){
            if(!$request->is('api/*'))
            {
                return null;
            }
            return $this->errorResponse($e->getMessage() , $e->getCode());
        });

        $this->renderable(function (ValidationException $e , $request){
            if(!$request->is('api/*'))
            {
                return null;
            }
            $firstError = collect($e->errors())->flatten()->first();
            return $this->errorResponse($firstError ,422);
        });

        $this->renderable(function (NotFoundHttpException $e , $request){
            if(!$request->is('api/*'))
            {
                return null;
            }
            return $this->errorResponse('Resource not found' , 404);
        });

        $this->renderable(function (ThrottleRequestsException $e , $request){
            if(!$request->is('api/*'))
            {
                return null;
            }
            $headers = $e->getHeaders();
            $retryAfter = $headers['Retry-After'] ?? null;

            return $this->errorResponse(" الكثير من المحاولات ، قم بالمحاولة مرة اخرى بعد $retryAfter ثانية " , 429);
        });

        $this->renderable(function (ModelNotFoundException $e , $request){
            if(!$request->is('api/*'))
            {
                return null;
            }
            return $this->errorResponse('العنصر الذي تحاول الوصول اليه غير موجود في النظام' , 404);
        });
    }
}
