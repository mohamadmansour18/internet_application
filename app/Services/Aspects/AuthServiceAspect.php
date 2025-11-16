<?php

namespace App\Services\Aspects;

use App\Services\Contracts\AuthServiceInterface;

class AuthServiceAspect implements AuthServiceInterface
{
    public function __construct(
        protected AuthServiceInterface $inner
    )
    {}
}
