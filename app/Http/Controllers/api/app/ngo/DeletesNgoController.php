<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Models\PendingTask;
use App\Traits\Ngo\NgoTrait;
use Illuminate\Http\Request;
use App\Enums\Type\TaskTypeEnum;
use App\Models\PendingTaskContent;
use App\Models\PendingTaskDocument;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Models\Ngo;
use App\Traits\Address\AddressTrait;
use App\Repositories\ngo\NgoRepositoryInterface;
use Illuminate\Foundation\Console\ViewMakeCommand;

class DeletesNgoController extends Controller
{
    use AddressTrait, NgoTrait;
    protected $ngoRepository;

    public function __construct(NgoRepositoryInterface $ngoRepository)
    {
        $this->ngoRepository = $ngoRepository;
    }

    public function destroyPersonalDetail(Request $request, $id)
    {
        $locale = App::getLocale();
        $user = $request->user();
        $user_id = $user->id;
        $role = $user->role_id;
        $task_type = TaskTypeEnum::ngo_registeration;

        // 1. Get PendingTask
        $task = PendingTask::where('user_id', $user_id)
            ->where('user_type', $role)
            ->where('task_type', $task_type)
            ->where('task_id', $id)
            ->first(); // Fetch the first matching record

        if (!$task) {
            return response()->json([
                "message" => __('app_translation.not_found'),
            ], 404);
        }

        // 2. Delete related PendingTaskContent records
        PendingTaskContent::where('pending_task_id', $task->id)->delete();
        // 3. Delete related PendingTaskDocument
        $pendingDocuments = PendingTaskDocument::where('pending_task_id', $task->id)->get();

        // 2. Loop through each PendingTaskContent record
        foreach ($pendingDocuments as $document) {
            // Check if the file exists in storage
            if ($this->tempFileExist($document->path)) {
                $this->deleteTempFile($document->path);
            }
            // 3. Delete the Document
            $document->delete();
        }
        // Delete the task itself
        $task->delete();

        $query = $this->ngoRepository->ngo();  // Start with the base query
        $this->ngoRepository->typeTransJoin($query, $locale)
            ->emailJoin($query)
            ->contactJoin($query)
            ->addressJoin($query);
        $ngo = $query->select(
            'n.abbr',
            'n.ngo_type_id',
            'ntt.value as type_name',
            'n.registration_no',
            'n.moe_registration_no',
            'n.place_of_establishment',
            'n.date_of_establishment',
            'a.province_id',
            'a.district_id',
            'a.id as address_id',
            'e.value as email',
            'c.value as contact'
        )->where('n.id', $id)->first();

        // Fetching translations using a separate query
        $translations = $this->ngoNameTrans($id);
        $areaTrans = $this->getAddressAreaTran($ngo->address_id);
        $address = $this->getCompleteAddress($ngo->address_id, $locale);


        $data = [
            'name_english' => $translations['en']->name ?? null,
            'name_pashto' => $translations['ps']->name ?? null,
            'name_farsi' => $translations['fa']->name ?? null,
            'abbr' => $ngo->abbr,
            'type' => ['name' => $ngo->type_name, 'id' => $ngo->ngo_type_id],
            'contact' => $ngo->contact,
            'email' => $ngo->email,
            'province' => ['name' => $address['province'], 'id' => $ngo->province_id],
            'district' => ['name' => $address['district'], 'id' => $ngo->district_id],
            'area_english' => $areaTrans['en']->area ?? '',
            'area_pashto' => $areaTrans['ps']->area ?? '',
            'area_farsi' => $areaTrans['fa']->area ?? '',
        ];
        return response()->json([
            "message" => __('app_translation.success'),
            'ngo' => $data,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function deleteProfile($id)
    {
        $ngo = Ngo::find($id);
        if ($ngo) {
            $deletePath = storage_path('app/' . "{$ngo->profile}");
            if (file_exists($deletePath) && $ngo->profile != null) {
                unlink($deletePath);
            }
            // 2. Update the profile
            $ngo->profile = null;
            $ngo->save();
            return response()->json([
                'message' => __('app_translation.profile_changed')
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 404, [], JSON_UNESCAPED_UNICODE);
    }
}
