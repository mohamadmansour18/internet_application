<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintLock extends Model
{
    use HasFactory;

    protected $table = 'complaint_locks';

    protected $fillable = [
        'complaint_id',
        'locked_by',
        'locked_at',
        'expired_at'
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class , 'complaint_id' , 'id')->withDefault();
    }
}
