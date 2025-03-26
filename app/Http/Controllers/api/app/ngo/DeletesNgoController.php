<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Models\Ngo;
use App\Models\PendingTask;
use App\Traits\Ngo\NgoTrait;
use Illuminate\Http\Request;
use App\Enums\Type\TaskTypeEnum;
use App\Models\PendingTaskContent;
use App\Traits\Helper\HelperTrait;
use App\Models\PendingTaskDocument;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use App\Traits\Address\AddressTrait;
use App\Repositories\ngo\NgoRepositoryInterface;
use Illuminate\Foundation\Console\ViewMakeCommand;
use App\Repositories\Task\PendingTaskRepositoryInterface;

class DeletesNgoController extends Controller
{
    use AddressTrait, NgoTrait, HelperTrait;
    protected $ngoRepository;
    protected $pendingTaskRepository;


    public function __construct(
        NgoRepositoryInterface $ngoRepository,
        PendingTaskRepositoryInterface $pendingTaskRepository
    ) {
        $this->ngoRepository = $ngoRepository;
        $this->pendingTaskRepository = $pendingTaskRepository;
    }

    public function destroyPendingTask(Request $request, $id)
    {
        $request->validate([
            'task_type' => "required"
        ]);
        $authUser = $request->user();
        $task_type = $request->task_type;

        $this->pendingTaskRepository->destroyPendingTask(
            $authUser,
            $task_type,
            $id
        );
        return response()->json([
            "message" => __('app_translation.success'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
