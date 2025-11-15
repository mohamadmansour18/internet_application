<?php

namespace App\Models;

use App\Enums\AgencyName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    use HasFactory;

    protected $table = 'agencies';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'name' =>  AgencyName::class ,
    ];

    public function Complaints(): HasMany
    {
        return $this->hasMany(Complaint::class , 'agency_id' , 'id');
    }
}
