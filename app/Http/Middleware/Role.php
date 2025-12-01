<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class Role
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next , string $role): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            throw new ApiException("غير مصرح لك بالدخول" , 403);
        }

        if ($role === 'both') {
            if (!in_array($user->role->value, [UserRole::MANAGER->value, UserRole::OFFICER->value])) {
                throw new ApiException("غير مصرح لك بالدخول الى لوحة التحكم", 403);
            }

            return $next($request);
        }

        if ($user->role->value !== $role) {
            throw new ApiException("غير مصرح لك بالدخول" , 403);
        }

        return $next($request);
    }
}
