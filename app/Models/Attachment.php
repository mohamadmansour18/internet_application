<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';

    protected $fillable = [
        'complaint_id',
        'uploaded_by',
        'path',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class , 'complaint_id' , 'id')->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class , 'uploaded_by', 'id')->withDefault();
    }
}
