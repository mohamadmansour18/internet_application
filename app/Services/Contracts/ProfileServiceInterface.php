<?php

namespace App\Services\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProfileServiceInterface
{
    public function paginateCitizens(int $perPage = 10, int $page = 1):LengthAwarePaginator ;
    public function paginateOfficers(int $perPage = 10, int $page = 1): LengthAwarePaginator ;
    public function deactivateUser(int $userId): void;
    public function activateUser(int $userId): void;
    public function createOfficer(array $data):User;

}
