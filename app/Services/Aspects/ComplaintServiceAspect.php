<?php

namespace App\Services\Aspects;

use App\Services\Contracts\ComplaintServiceInterface;

class ComplaintServiceAspect implements ComplaintServiceInterface
{
    public function __construct(
        protected ComplaintServiceInterface $inner
    )
    {}
}
