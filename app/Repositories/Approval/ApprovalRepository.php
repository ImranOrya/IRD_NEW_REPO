<?php

namespace App\Repositories\Approval;

use App\Models\Approval;
use App\Models\ApprovalDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Enums\Type\ApprovalTypeEnum;

class ApprovalRepository implements ApprovalRepositoryInterface
{
    public function ngoApproval($approval_id)
    {
        $locale = App::getLocale();
        $approval = DB::table('approvals as a')
            ->where("a.id", $approval_id)
            ->leftJoin('users as u', function ($join) {
                $join->on('u.id', '=', 'a.responder_id');
            })
            ->join('ngo_trans as nt', function ($join) use ($locale) {
                $join->on('nt.ngo_id', '=', 'a.requester_id')
                    ->where('nt.language_name', $locale);
            })
            ->join('agreements as ag', function ($join) use ($locale) {
                $join->on('ag.ngo_id', '=', 'a.requester_id')
                    ->latest('ag.end_date');
            })
            ->join('notifier_type_trans as ntt', function ($join) use ($locale) {
                $join->on('ntt.notifier_type_id', '=', 'a.notifier_type_id')
                    ->where('ntt.language_name', $locale);
            })
            ->join('approval_documents as ad', 'ad.approval_id', '=', 'a.id')
            ->join('check_list_trans as ct', function ($join) use ($locale) {
                $join->on('ct.check_list_id', '=', 'ad.check_list_id')
                    ->where('ct.language_name', $locale);
            })
            ->select(
                'a.id',
                'a.completed',
                'a.requester_id',
                'nt.name as requester_name',
                'a.request_date',
                'ag.start_date',
                'ag.end_date',
                "a.request_comment",
                'a.responder_id',
                'u.username as responder',
                'a.respond_date',
                "a.respond_comment",
                'a.notifier_type_id',
                'ntt.value as notifier_type',
                'ad.id as approval_id',
                'ad.path',
                'ad.actual_name as name',
                'ad.type as extension',
                'ad.size',
                'ct.value as checklist_name'
            )
            ->get();

        $approvalsWithDocuments = $approval->groupBy('id')->map(function ($approvalGroup) {
            $approval = $approvalGroup->first();
            $documents = $approvalGroup->map(function ($item) {
                return [
                    'id' => $item->approval_id,
                    'path' => $item->path,
                    'name' => $item->name,
                    'extension' => $item->extension,
                    'size' => $item->size,
                    'checklist_name' => $item->checklist_name,
                ];
            });

            $approval->approval_documents = $documents;
            unset($approval->approval_id, $approval->checklist_name, $approval->path, $approval->name, $approval->extension, $approval->size);  // Clean up extra fields

            return $approval;
        })->values();

        if (count($approvalsWithDocuments) != 0)
            return $approvalsWithDocuments->first();
        return null;
    }
    public function getByNotifierTypeAndRequesterType($approval_type_id, $requester_type)
    {
        $locale = App::getLocale();

        return DB::table('approvals as a')
            ->where("a.requester_type", $requester_type)
            ->where("a.approval_type_id", $approval_type_id)
            ->join('approval_type_trans as att', function ($join) use ($locale) {
                $join->on('att.approval_type_id', '=', 'a.approval_type_id')
                    ->where('att.language_name', $locale);
            })
            ->join('ngo_trans as nt', function ($join) use ($locale) {
                $join->on('nt.ngo_id', '=', 'a.requester_id')
                    ->where('nt.language_name', $locale);
            })
            ->join('notifier_type_trans as ntt', function ($join) use ($locale) {
                $join->on('ntt.notifier_type_id', '=', 'a.notifier_type_id')
                    ->where('ntt.language_name', $locale);
            })
            ->select(
                'a.id',
                'a.request_date',
                'a.requester_id',
                'a.responder_id',
                'a.responder_type',
                'a.notifier_type_id',
                'ntt.value as notifier_type',
                'nt.name as requester',
                DB::raw('(
                    SELECT COUNT(*)
                    FROM approval_documents as ad_count
                    WHERE ad_count.approval_id = a.id
                ) as document_count')
            );
    }
    public function storeApproval($requester_id, $requester_type, $notifier_type_id, $request_comment)
    {
        return Approval::create([
            "request_comment" => $request_comment,
            "requester_id" => $requester_id,
            "approval_type_id" => ApprovalTypeEnum::pending->value,
            "requester_type" => $requester_type,
            "notifier_type_id" => $notifier_type_id,
        ]);
    }
    public function storeApprovalDocument($approval_id, $documentData)
    {
        return ApprovalDocument::create([
            "approval_id" => $approval_id,
            'actual_name' => $documentData['actual_name'],
            'size' => $documentData['size'],
            'path' => $documentData['path'],
            'type' => $documentData['type'],
            'check_list_id' => $documentData['check_list_id'],
        ]);
    }
}
