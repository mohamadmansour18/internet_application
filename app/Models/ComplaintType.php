<?php

namespace App\Models;

use App\Enums\ComplaintTypeName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintType extends Model
{
    use HasFactory;

    protected $table = 'complaint_types';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'name' => ComplaintTypeName::class ,
    ];

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class , 'complaint_type_id' , 'id');
    }
}
