<?php

namespace App\Models;

use App\Enums\ComplaintCurrentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'complaint_status_histories';

    protected $fillable = [
        'complaint-id',
        'changed_by',
        'status',
    ];

    protected $casts = [
        'status' => ComplaintCurrentStatus::class,
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class , 'complaint-id' , 'id')->withDefault();
    }
}
