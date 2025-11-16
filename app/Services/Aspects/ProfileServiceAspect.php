<?php

namespace App\Services\Aspects;

use App\Services\Contracts\ProfileServiceInterface;

class ProfileServiceAspect implements ProfileServiceInterface
{
    public function __construct(
        protected ProfileServiceInterface $inner
    ){}
}
