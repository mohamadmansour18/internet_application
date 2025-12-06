<?php

namespace App\Models;

use App\Enums\ProfileCity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfile extends Model
{
    use HasFactory;

    protected $table = 'staff_profiles';

    protected $fillable = [
        'user_id',
        'agency_id',
        'phone',
        'profile_picture',
        'city',
        'job_title',
        'department',
    ];

    protected $casts = [
        'city' => ProfileCity::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class , 'user_id' , 'id')->withDefault();
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class , 'agency_id' , 'id')->withDefault();
    }
}
