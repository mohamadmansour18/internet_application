<?php

namespace App\Models;

use App\Enums\ComplaintCurrentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Complaint extends Model
{
    use HasFactory;

    protected $table = 'complaints';

    protected $fillable = [
        'citizen_id',
        'agency_id',
        'complaint_type_id',
        'assigned_officer_id',
        'title',
        'description',
        'location_text',
        'current_status',
        'number'
    ];

    protected $casts = [
        'current_status' => ComplaintCurrentStatus::class,
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class , 'citizen_id' , 'id')->withDefault();
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class , 'agency_id' , 'id')->withDefault();
    }

    public function complaintType(): BelongsTo
    {
        return $this->belongsTo(ComplaintType::class , 'complaint_type_id' , 'id')->withDefault();
    }

    public function assignOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class , 'assigned_officer_id' , 'id')->withDefault();
    }

    public function complaintHistories(): HasMany
    {
        return $this->hasMany(ComplaintStatusHistory::class , 'complaint_id' , 'id');
    }

    public function complaintLock(): HasOne
    {
        return $this->hasOne(ComplaintLock::class , 'complaint_id' , 'id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class , 'complaint_id' , 'id');
    }
}
