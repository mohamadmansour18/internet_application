<?php

namespace App\Repositories\Complaints_Domain;

use App\Models\Complaint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentRepository
{
    public function getCitizenComplaintWithAttachment(int $complaintId): Model|Builder|null
    {
        return  Complaint::query()
            ->with(['attachments'])
            ->where('id' , $complaintId)
            ->first();
    }

    public function softDeleteComplaintWithAttachment(mixed $complaint): void
    {
        DB::transaction(function () use ($complaint) {
            foreach ($complaint->attachments as $attachment)
            {
                $attachment->delete();
                Storage::disk('public')->delete($attachment->path);
            }

            $complaint->delete();
        });
    }
}
