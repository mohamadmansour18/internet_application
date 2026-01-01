<?php

namespace App\Services;

use App\Enums\AgencyName;
use App\Enums\ComplaintCurrentStatus;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Helpers\StorageUrlHelper;
use App\Models\Complaint;
use App\Models\User;
use App\Repositories\Complaints_Domain\AttachmentRepository;
use App\Repositories\Complaints_Domain\ComplaintRepository;
use App\Repositories\Complaints_Domain\ComplaintTypeRepository;
use App\Services\Contracts\ComplaintServiceInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mpdf\Mpdf;
use ZipArchive;

class ComplaintService implements ComplaintServiceInterface
{
    public function __construct(
        private readonly ComplaintRepository $complaintRepository,
        private readonly ComplaintTypeRepository $complaintTypeRepository,
        private readonly AttachmentRepository $attachmentRepository,
    )
    {}

    public function getCitizenComplaints(int $citizenId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->complaintRepository->paginateCitizenComplaints($citizenId , $perPage , $page);
    }

    public function SearchComplaint(int $citizenId, int $number): array
    {
        $complaint = $this->complaintRepository->findCitizenComplaintByNumber($citizenId , $number);

        if(!$complaint)
        {
            return [];
        }

        return [
            'complaint_number' => $complaint->number,
            'title'            => $complaint->title,
            'description'      => $complaint->description,
            'status'           => $complaint->current_status,
        ];
    }

    public function createCitizenComplaint(int $citizenId, array $data, array $attachments = []): Complaint
    {
        $attachmentsPayload = [];
        foreach ($attachments as $file) {
            //storedPath : complaints/example.png
            $storedPath = $file->store('complaints' , 'public');

            $attachmentsPayload[]=[
                'uploaded_by' => $citizenId,
                'path' => $storedPath,
            ];
        }

        return $this->complaintRepository->createCitizenComplaint($citizenId , $data , $attachmentsPayload);
    }

    public function getComplaintTypeByName()
    {
        return $this->complaintTypeRepository->getAllComplaintTypeName();
    }

    public function getCitizenComplaintDetails(int $citizenId, int $complaintId): array
    {
        $complaint = $this->complaintRepository->getCitizenComplaintDetails($citizenId , $complaintId);

        if(!$complaint)
        {
            throw new ApiException("العنصر الذي تحاول الوصول الى تفاصيله غير موجود اساسا" , 404);
        }

        //(1) complaint attachments
        $attachments = $complaint->attachments->map(function($attachment){
            return [
                'id' => $attachment->id,
                'path' => StorageUrlHelper::getImagePath($attachment->path),
            ];
        })->values()->toArray();

        //(2) complaint information
        $complaintInfo = [
            'complaint_type' => $complaint->complaintType->name,
            'agency'         => $complaint->agency->name,
            'created_at'     => $complaint->created_at->format('Y-m-d'),
            'complaint_number' => $complaint->number,
            'title'            => $complaint->title,
            'description'      => $complaint->description,
            'status'           => $complaint->current_status->value,
            'location_text'    => $complaint->location_text,
        ];

        //(3) complaint history
        Carbon::setLocale('ar');

        $history = $complaint->complaintHistories->map(function($complaintHistory){
           return [
               'day' => $complaintHistory->created_at->translatedFormat('l'),
               'date' => $complaintHistory->created_at->format('Y-m-d'),
               'status' => $complaintHistory->status,
               'note' => $complaintHistory->note ?? null,
           ];
        })->values()->toArray();

        return [
            'attachments'    => $attachments,
            'complaint_info' => $complaintInfo,
            'history'        => $history,
        ];
    }

    public function deleteCitizenComplaint(int $complaintId): void
    {
        $complaint = $this->attachmentRepository->getCitizenComplaintWithAttachment($complaintId);

        if(!$complaint)
        {
            throw new ApiException("العنصر الذي تحاول الوصول الى تفاصيله غير موجود اساسا" , 404);
        }

        if($complaint->current_status === ComplaintCurrentStatus::IN_PROGRESS)
        {
            throw new ApiException("لايمكن حذف شكوى حالتها قيد المعالجة" , 422);
        }

        $this->attachmentRepository->softDeleteComplaintWithAttachment($complaint);
    }

    public function addExtraInfoToComplaint(int $complaintId , int $citizenId , ?string $extraText, ?UploadedFile $extraAttachment): void
    {
        $complaint = $this->complaintRepository->findCitizenComplaint($complaintId);

        if(!$complaint)
        {
            throw new ApiException("العنصر الذي تحاول الوصول الى تفاصيله غير موجود اساسا" , 404);
        }

        if($complaint->current_status !== ComplaintCurrentStatus::NEED_INFORMATION)
        {
            throw new ApiException("لا يمكنك إضافة معلومات إضافية إلا إذا كانت حالة الشكوى تحتاج إلى معلومات إضافية" , 422);
        }

        if($complaint->has_extra_info)
        {
            throw new ApiException("لقد قمت مسبقًا بإضافة معلومات إضافية لهذه الشكوى" , 422);
        }

        $attachmentPayload = null;

        if ($extraAttachment instanceof UploadedFile)
        {
            $storedPath = $extraAttachment->store('complaints' , 'public');

            $attachmentPayload = [
                'uploaded_by' => $citizenId,
                'path' => $storedPath,
            ];
        }

        $this->complaintRepository->addExtraInfo($complaint , $extraText , $attachmentPayload);
    }

    //--------------------------------------<DASHBOARD>--------------------------------------//

    public function getComplaintBasedRole(User $user, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $agencyId = null ;

        if($user->role->value === UserRole::OFFICER->value) {
            $agencyId = $user->staffProfile->agency_id;
        }
        return $this->complaintRepository->PaginateComplaintsForDashboard($user->role->value , $user->id , $agencyId , $perPage , $page);
    }

    public function ComplaintDetails(int $complaintId): array
    {
        $complaint = $this->complaintRepository->getComplaintDetails($complaintId);

        if(!$complaint)
        {
            throw new ApiException("العنصر الذي تحاول الوصول الى تفاصيله غير موجود اساسا" , 404);
        }

        //(1) complaint attachments
        $attachments = $complaint->attachments->map(function($attachment){
            return [
                'id' => $attachment->id,
                'path' => StorageUrlHelper::getImagePath($attachment->path),
            ];
        })->values()->toArray();

        $complaintInfo = [
            'officer' => $complaint->assignOfficer->name ?? "لم تستلم بعد",
            'complaint_type' => $complaint->complaintType->name,
            'agency'         => $complaint->agency->name,
            'created_at'     => $complaint->created_at->format('Y-m-d'),
            'complaint_number' => $complaint->number,
            'title'            => $complaint->title,
            'description'      => $complaint->description,
            'status'           => $complaint->current_status->value,
            'location_text'    => $complaint->location_text,
            'extra'            => $complaint->extra ?? "لا يوجد معلومات اضافية",
        ];

        //(3) complaint history
        Carbon::setLocale('ar');

        $history = $complaint->complaintHistories->map(function($complaintHistory){
            return [
                'day' => $complaintHistory->created_at->translatedFormat('l'),
                'date' => $complaintHistory->created_at->format('Y-m-d'),
                'status' => $complaintHistory->status,
                'changed_by' => $complaintHistory->user->name ?? 'لم تستلم بعد',
                'note' => $complaintHistory->note ?? "لايوجد",
            ];
        })->values()->toArray();

        return [
            'attachments'    => $attachments,
            'complaint_info' => $complaintInfo,
            'history'        => $history,
        ];
    }

    public function StartProcessingComplaint(int $userId, int $complaintId, ?string $note = null): Complaint
    {
        $complaint = $this->complaintRepository->findComplaintById($complaintId);

        if(!$complaint)
        {
            throw new ApiException('الشكوى التي تحاول الوصول اليها غير موجودة', 404);
        }

        if($complaint->current_status->value !== ComplaintCurrentStatus::NEW->value)
        {
            throw new ApiException("لايمكنك معالجة شكوى حالتها ليست معلقة" , 422);
        }

        return $this->complaintRepository->startProcessComplaint($complaintId , $userId , $note);
    }

    public function rejectComplaint(int $userId, int $complaintId, ?string $note = null): Complaint
    {
        $complaint = $this->complaintRepository->findComplaintById($complaintId);

        if(!$complaint)
        {
            throw new ApiException('الشكوى التي تحاول الوصول اليها غير موجودة', 404);
        }
        if($complaint->current_status->value === ComplaintCurrentStatus::DONE->value)
        {
            throw new ApiException("لايمكنك رفض شكوى بعد ان تم معالجتها" , 422);
        }
        if($complaint->current_status->value === ComplaintCurrentStatus::REJECTED->value)
        {
            throw new ApiException("لايمكنك رفض شكوى هي اساسا مرفوضة" , 422);
        }
        if($complaint->current_status->value === ComplaintCurrentStatus::NEW->value)
        {
            throw new ApiException("لايمكنك رفض شكوى لم تقم بوضعها في حالة قيد المعالجة" , 422);
        }

        return $this->complaintRepository->rejectComplaint($complaintId , $userId , $note);
    }

    public function finishComplaint(int $userId, int $complaintId, ?string $note = null): Complaint
    {
        $complaint = $this->complaintRepository->findComplaintById($complaintId);

        if(!$complaint)
        {
            throw new ApiException('الشكوى التي تحاول الوصول اليها غير موجودة', 404);
        }

        if($complaint->current_status->value === ComplaintCurrentStatus::REJECTED->value)
        {
            throw new ApiException("لايمكنك معالجة شكوى بعد ان تم رفضها" , 422);
        }

        if($complaint->current_status->value === ComplaintCurrentStatus::DONE->value)
        {
            throw new ApiException("لايمكنك اتمام معالجة شكوى قد تم معالجتها" , 422);
        }

        if($complaint->current_status->value === ComplaintCurrentStatus::NEW->value)
        {
            throw new ApiException("لايمكنك اتمام معالجة الشكوى مباشرة دون دراستها ووضعها تحت قيد المعالجة" , 422);
        }

        return $this->complaintRepository->acceptComplaint($complaint , $userId , $note);
    }

    public function requestMoreInfoToComplaint(int $userId, int $complaintId, string $note): Complaint
    {
        $complaint = $this->complaintRepository->findComplaintById($complaintId);

        if(!$complaint)
        {
            throw new ApiException('الشكوى التي تحاول الوصول اليها غير موجودة', 404);
        }

        if($complaint->current_status->value !== ComplaintCurrentStatus::IN_PROGRESS->value)
        {
            throw new ApiException("لايمكنك طلب معلومات اضافية من هذه الشكوى" , 422);
        }

        return $this->complaintRepository->addMoreInfoToComplaint($complaint , $userId , $note);

    }

    //--------------------------------------<ADMIN>--------------------------------------//

    public function getComplaintStatsByMonthForDashboard(int $month): array
    {
        $stats = $this->complaintRepository->getMonthlyComplaintStatsByAgency($month);

        $result = [];

        foreach (AgencyName::cases() as $agencyCase)
        {
            $agencyName = $agencyCase->value;
            $row = $stats->firstwhere(fn($stat) => $stat->agency?->name->value === $agencyName);

            $pending = (int) ($row->pending_count ?? 0);
            $rejected = (int) ($row->rejected_count ?? 0);
            $resolved = (int) ($row->resolved_count ?? 0);
            $total    = (int) ($row->total_count    ?? 0);


            $result[$agencyName] = [
                'pending'  => $pending,
                'rejected' => $rejected,
                'resolved' => $resolved,
                'total'    => $total,
            ];
        }
        return $result;
    }

    public function getYearlyComplaintSummaryForDashboard(): array
    {
        return $this->complaintRepository->getYearlyComplaintSummary();
    }

    //--------------------------------------<PDF & CSV>--------------------------------------//
    public function getYearlyStatsForExport(): array
    {
        $year = now()->year;
        $stats = $this->complaintRepository->getYearlyComplaintStatsByAgency();

        $result = [];

        foreach ($stats as $row)
        {
            $agencyName = $row->agency?->name->value;

            $pending       = (int) $row->pending_count;
            $rejected      = (int) $row->rejected_count;
            $resolved      = (int) $row->resolved_count;
            $underProc     = (int) $row->under_processing_count;
            $needsExtra    = (int) $row->needs_additional_info_count;
            $total         = (int) $row->total_count;

            $result[] = [
                'agency_name'              => $agencyName,
                'pending'                  => $pending,
                'rejected'                 => $rejected,
                'resolved'                 => $resolved,
                'under_processing'         => $underProc,
                'needs_additional_info'    => $needsExtra,
                'total'                    => $total,
            ];
        }

        return [
            'year' => $year ,
            'items' => $result,
        ];
    }

    public function generateYearlyStatsReport(string $format = 'pdf'): array
    {
        $format = strtolower($format);

        //Get data for report
        $data  = $this->getYearlyStatsForExport();
        $year  = $data['year'];
        $items = $data['items'];

        //make sure that folder is exists
        Storage::disk('public')->makeDirectory('stats');

        $baseName     = "complaints_stats_{$year}";

        try {
            if ($format === 'csv') {

                $relativePath = "stats/{$baseName}.csv}";
                $fullPath     = Storage::disk('public')->path($relativePath);
                $this->generateCsvFile($fullPath, $items, $year);

                return ['full_path' => $fullPath, 'file_name' => "{$baseName}.csv"];
            }
            if($format === 'zip'){
                //temp pdf file
                $pdfRelativePath = "stats/{$baseName}.pdf";
                $pdfFullPath     = Storage::disk('public')->path($pdfRelativePath);
                $this->generatePdfFile($pdfFullPath, $items, $year);

                //compress pdf file
                $zipRelativePath = "stats/{$baseName}.zip";
                $zipFullPath     = Storage::disk('public')->path($zipRelativePath);

                $this->createZipWithSingleFile(
                    zipFullPath: $zipFullPath,
                    fileToAddFullPath: $pdfFullPath,
                    fileNameInsideZip: "{$baseName}.pdf"
                );

                @unlink($pdfFullPath);

                return ['full_path' => $zipFullPath, 'file_name' => "{$baseName}.zip"];
            }

            $relativePath = "stats/{$baseName}.pdf";
            $fullPath     = Storage::disk('public')->path($relativePath);
            $this->generatePdfFile($fullPath, $items, $year);

            return ['full_path' => $fullPath, 'file_name' => "{$baseName}.pdf"];

        }catch (\Throwable $exception)
        {
            Log::error('PDF generation failed' , [
                'message' => $exception->getMessage(),
                'trace'   => $exception->getTraceAsString(),
            ]);
            throw new ApiException('! حدث خطأ غير متوقع اثناء التنفيذ', $exception->getCode());
        }
    }

    protected function generateCsvFile(string $fullPath, array $items, int $year): void
    {
        $handle = fopen($fullPath, 'w');

        fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($handle, [
            'الجهة الحكومية',
            "السنة {$year}",
            'معلقة',
            'قيد المعالجة',
            'معلومات اضافية',
            'مرفوضة',
            'تم انجازها',
            'المجموع',
        ]);

        foreach ($items as $row) {
            fputcsv($handle, [
                $row['agency_name'],
                $year,
                $row['pending'],
                $row['under_processing'],
                $row['needs_additional_info'],
                $row['rejected'],
                $row['resolved'],
                $row['total'],
            ]);
        }

        fclose($handle);

    }

    protected function generatePdfFile(string $fullPath, array $items, int $year): void
    {
        $mpdfTemp = storage_path('app/mpdf-temp');
        if (!File::exists($mpdfTemp)) {
            File::makeDirectory($mpdfTemp, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'directionality'    => 'rtl',
            'autoLangToFont'    => true,
            'autoScriptToLang'  => true,
            'tempDir'           => $mpdfTemp,
            'margin_top'        => 15,
            'margin_bottom'     => 15,
            'margin_left'       => 10,
            'margin_right'      => 10,
        ]);

        $mpdf->SetCompression(true);

        $html = view('reports.yearly_complaints_stats', [
            'year'  => $year,
            'items' => $items,
        ])->render();

        $mpdf->WriteHTML($html);
        $mpdf->Output($fullPath, \Mpdf\Output\Destination::FILE);
    }

    protected function createZipWithSingleFile(string $zipFullPath, string $fileToAddFullPath, string $fileNameInsideZip): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new ApiException("Cannot create zip file at: {$zipFullPath}" , 422);
        }

        if (!$zip->addFile($fileToAddFullPath, $fileNameInsideZip)) {
            $zip->close();
            throw new ApiException("Cannot add file to zip: {$fileToAddFullPath}" , 422);
        }

        $zip->setCompressionName($fileNameInsideZip, ZipArchive::CM_DEFLATE);

        $zip->close();
    }
}
