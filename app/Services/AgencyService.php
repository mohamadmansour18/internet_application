<?php

namespace App\Services;

use App\Repositories\Agency_Domain\AgencyRepository;
use App\Services\Contracts\AgencyServiceInterface;

class AgencyService implements AgencyServiceInterface
{
    public function __construct(
        private readonly AgencyRepository $agencyRepository,
    )
    {}

    public function getAgencyByName()
    {
        return $this->agencyRepository->getAllAgencyName();
    }
}
