<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();
        if(!$user || $user->role->value !== $role)
        {
            throw new ApiException("غير مصرح لك بالدخول" , 403);
        }
        return $next($request);
    }
}
