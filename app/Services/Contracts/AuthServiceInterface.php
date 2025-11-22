<?php

namespace App\Services\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    public function registerCitizen(array $data): array ;
    public function loginCitizen(array $data): array ;
}
