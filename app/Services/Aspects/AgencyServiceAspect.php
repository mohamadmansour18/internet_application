<?php

namespace App\Services\Aspects;

use App\Services\Contracts\AgencyServiceInterface;
use App\Services\Contracts\ComplaintServiceInterface;

class AgencyServiceAspect implements AgencyServiceInterface
{
    public function __construct(
        protected AgencyServiceInterface $inner
    )
    {}
}
