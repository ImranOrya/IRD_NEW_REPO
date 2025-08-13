<?php

namespace App\Http\Controllers\api\app\schedule;

use App\Enums\Status\SchedualStatusEnum;
use App\Enums\Status\ScheduleStatusEnum;
use App\Models\Schedule;
use App\Models\ScheduleItem;
use Illuminate\Http\Request;
use App\Enums\Status\StatusEnum;
use App\Enums\Type\TaskTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB as FacadesDB;
use App\Http\Requests\app\schedule\ScheduleRequest;
use App\Http\Requests\app\schedule\ScheduleUpdateRequest;
use App\Models\Document as ModelsDocument;
use App\Repositories\Storage\StorageRepositoryInterface;
use App\Repositories\PendingTask\PendingTaskRepositoryInterface;
use App\Models\Document;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\ScheduleDocument;
use App\Models\ScheduleItemStatus;
use Carbon\Carbon;

class ScheduleController extends Controller
{


    protected $pendingTaskRepository;
    protected $storageRepository;

    public function __construct(
        PendingTaskRepositoryInterface $pendingTaskRepository,
        StorageRepositoryInterface $storageRepository
    ) {
        $this->pendingTaskRepository = $pendingTaskRepository;

        $this->storageRepository = $storageRepository;
    }

    public function schedules(Request $request)
    {


        $locale = App::getLocale();
        $request = DB::table('schedules as sch')
            ->join('schedule_status_trans as scht', function ($join) use ($locale) {
                $join->on('scht.schedule_status_id', 'sch.schedule_status_id')
                    ->where('scht.language_name', $locale);
            })
            ->select('sch.id', 'scht.value as status', 'sch.date')->get();


        return response()->json(
            $request
        );
    }
    public function prepareSchedule(Request $request)
    {
        $count = $request->count ?? 10;

        // Decode the string '[1,2]' → array [1, 2]

        $ids = $request->input('ids');
        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [];
        }


        $locale = App::getLocale();

        // 1. Get the projects by incoming ids (if any)
        $projectsFromIds = collect();
        if (!empty($ids)) {
            $projectsFromIds = DB::table('projects as pro')
                ->join('project_statuses as pros', function ($join) {
                    $join->on('pro.id', '=', 'pros.project_id')
                        ->where('is_active', true);
                })
                ->join('project_trans as prot', function ($join) use ($locale) {
                    $join->on('prot.project_id', '=', 'pro.id')
                        ->where('prot.language_name', $locale);
                })
                ->whereIn('pro.id', $ids)
                ->where('pros.status_id', StatusEnum::pending_for_schedule->value)
                ->select('pro.id', 'prot.name')
                ->get();
        }


        $fetchedCount = $projectsFromIds->count();
        $remainingCount = $count - $fetchedCount;


        $remainingProjects = collect();

        if ($remainingCount > 0) {
            $query = DB::table('projects as pro')
                ->join('project_statuses as pros', function ($join) {
                    $join->on('pro.id', '=', 'pros.project_id')
                        ->where('is_active', true);
                })->join('project_trans as prot', function ($join) use ($locale) {
                    $join->on('prot.project_id', '=', 'pro.id')
                        ->where('prot.language_name', $locale);
                })
                ->where('pros.status_id', StatusEnum::pending_for_schedule->value)
                ->select('pro.id', 'prot.name');

            if ($remainingCount != $count) {
                $query->whereNotIn('pro.id', $ids);
            }

            $remainingProjects = $query->limit($remainingCount)->get();
        }



        // 3. Merge both and return
        $projects = $projectsFromIds->merge($remainingProjects);

        return response()->json($projects);
    }

    public function store(ScheduleRequest $request)
    {
        DB::beginTransaction();

        $authUser = $request->user();

        // Create Schedule
        $schedule = Schedule::create([
            'date' => Carbon::parse($request['date'])->toDateString() ?? now()->toDateString(),
            'start_time' => $request['start_time'] ?? '08:00',
            'end_time' => $request['end_time'],
            'representators_count' => $request['presentation_count'],
            'presentation_lenght' => $request['presentation_length'],
            'gap_between' => $request['gap_between'],
            'lunch_start' => $request['lunch_start'],
            'lunch_end' => $request['lunch_end'],
            'dinner_start' => $request['dinner_start'],
            'dinner_end' => $request['dinner_end'],
            'presentation_before_lunch' => $request['presentation_count'] - $request['presentations_after_lunch'],
            'presentation_after_lunch' => $request['presentations_after_lunch'],
            'is_hour_24' => $request['time_format24h'] ?? false,
            'schedule_status_id' => ScheduleStatusEnum::Scheduled->value
        ]);

        foreach ($request['scheduleItems'] as $item) {
            $scheduleItem = $this->createScheduleItem($schedule->id, $item);

            ScheduleItemStatus::create([
                'schedule_item_id' => $scheduleItem->id,
                'schedule_status_id' => ScheduleStatusEnum::Scheduled->value,
                'discription' => 'Schedule the project'
            ]);

            $this->updateProjectStatus($authUser, $item['projectId']);

            if (!empty($item['attachment'])) {
                $this->handleAttachment($item['attachment'], $schedule->id, $scheduleItem->id);
            }
        }

        DB::commit();
        return response()->json(['message' => __('app_translation.success')], 200);
    }


    public function edit($id)
    {
        $locale = App::getLocale();
        // Fetch the schedule
        $schedule = DB::table('schedules')->where('id', $id)->first();

        if (!$schedule) {
            return response()->json(['message' => __('app_translation.not_found')], 404);
        }

        // Fetch schedule items
        $scheduleItems = DB::table('schedule_items as schi')
            ->join('project_trans as prot', function ($join) use ($locale) {
                $join->on('schi.project_id', '=', 'prot.project_id')
                    ->where('prot.language_name', $locale);
            })
            ->where('schedule_id', $id)
            ->select(
                'prot.name as project_name',
                'schi.*'
            )
            ->get();

        $formattedItems = [];

        foreach ($scheduleItems as $item) {
            // Fetch the related document (if exists)
            $scheduleDocument = DB::table('schedule_documents')
                ->where('schedule_item_id', $item->id)
                ->first();

            $document = null;
            if ($scheduleDocument) {
                $doc = DB::table('documents')->where('id', $scheduleDocument->document_id)->first();
                if ($doc) {
                    $document = [
                        'document_id' => $doc->id, // You may store actual  elsewhere
                        'name' => $doc->actual_name,
                        'size' => $doc->size,
                        'check_list_id' => $doc->check_list_id,
                        'extension' => $doc->type,
                        'path' => $doc->path
                    ];
                }
            }

            // Calculate gap_end
            $gapEnd = Carbon::parse($item->end_time)
                ->addMinutes($schedule->gap_between)
                ->format('H:i');

            $formattedItems[] = [
                'slot' => [
                    'id' => $item->id,
                    'presentation_start' => $item->start_time,
                    'presentation_end' => $item->end_time,
                    'gap_end' => $gapEnd
                ],
                'projectId' => $item->project_id,
                'project_name' => $item->project_name,
                'attachment' => $document,
                'selected' => false
            ];
        }

        // Final data structure
        $data = [
            'date' => $schedule->date,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'dinner_start' => $schedule->dinner_start,
            'dinner_end' => $schedule->dinner_end,
            'gap_between' => $schedule->gap_between,
            'lunch_start' => $schedule->lunch_start,
            'lunch_end' => $schedule->lunch_end,
            'presentation_length' => $schedule->presentation_lenght,
            'presentation_count' => $schedule->representators_count,
            'presentations_after_lunch' => $schedule->presentation_after_lunch,
            'time_format24h' => $schedule->is_hour_24,
            'scheduleItems' => $formattedItems
        ];

        return response()->json($data);
    }

    public function update(ScheduleUpdateRequest $request, $id)
    {
        DB::beginTransaction();

        $authUser = $request->user();

        $schedule = Schedule::findOrFail($id);

        $schedule->update([
            'date' => Carbon::parse($request['date'])->toDateString() ?? now()->toDateString(),
            'start_time' => $request['start_time'] ?? '08:00',
            'end_time' => $request['end_time'],
            'representators_count' => $request['presentation_count'],
            'presentation_lenght' => $request['presentation_length'],
            'gap_between' => $request['gap_between'],
            'lunch_start' => $request['lunch_start'],
            'lunch_end' => $request['lunch_end'],
            'dinner_start' => $request['dinner_start'],
            'dinner_end' => $request['dinner_end'],
            'presentation_before_lunch' => $request['presentation_count'] - $request['presentations_after_lunch'],
            'presentation_after_lunch' => $request['presentations_after_lunch'],
            'is_hour_24' => $request['time_format24h'] ?? false,
            'schedule_status_id' => ScheduleStatusEnum::Scheduled->value,
        ]);

        $existingItemIds = ScheduleItem::where('schedule_id', $id)->pluck('id')->toArray();
        $processedItemIds = [];

        foreach ($request['scheduleItems'] as $item) {
            $scheduleItem = null;

            if (!empty($item['slot']['id']) && in_array($item['slot']['id'], $existingItemIds)) {
                $scheduleItem = ScheduleItem::find($item['slot']['id']);
                if ($scheduleItem && $scheduleItem->project_id === $item['projectId']) {
                    $scheduleItem->update([
                        'start_time' => $item['slot']['presentation_start'],
                        'end_time' => $item['slot']['presentation_end'],
                    ]);
                } else {
                    $scheduleItem->delete();
                    $scheduleItem = $this->createScheduleItem($schedule->id, $item);
                }
            } else {
                $scheduleItem = $this->createScheduleItem($schedule->id, $item);
            }

            if ($scheduleItem) {
                $processedItemIds[] = $scheduleItem->id;

                if (!empty($item['attachment'])) {
                    $this->handleAttachment($item['attachment'], $schedule->id, $scheduleItem->id);
                }

                $this->updateProjectStatus($authUser, $item['projectId']);
            }
        }

        // Optional cleanup
        // ScheduleItem::where('schedule_id', $id)->whereNotIn('id', $processedItemIds)->delete();

        DB::commit();
        return response()->json(['message' => __('app_translation.success')]);
    }
    private function createScheduleItem($scheduleId, $item)
    {
        return ScheduleItem::create([
            'project_id' => $item['projectId'],
            'schedule_id' => $scheduleId,
            'start_time' => $item['slot']['presentation_start'],
            'end_time' => $item['slot']['presentation_end'],
        ]);
    }

    private function updateProjectStatus($authUser, $projectId)
    {
        ProjectStatus::where('project_id', $projectId)->update(['is_active' => false]);

        ProjectStatus::create([
            'is_active' => true,
            'project_id' => $projectId,
            'status_id' => StatusEnum::scheduled->value,
            'comment' => 'Schedule for the presentation',
            'userable_type' => $authUser->role_id,
            'userable_id' => $authUser->id,
        ]);
    }

    private function handleAttachment(array $attachment, $scheduleId, $scheduleItemId)
    {
        $this->storageRepository->scheduleDocumentStore($scheduleId, $attachment['pending_id'], function ($docData) use ($scheduleItemId) {
            $document = Document::create([
                'actual_name' => $docData['actual_name'],
                'size' => $docData['size'],
                'path' => $docData['path'],
                'type' => $docData['type'],
                'check_list_id' => $docData['check_list_id'],
            ]);

            ScheduleDocument::create([
                'document_id' => $document->id,
                'schedule_item_id' => $scheduleItemId,
            ]);
        });

        $this->pendingTaskRepository->destroyPendingTaskById($attachment['pending_id']);
    }
}
