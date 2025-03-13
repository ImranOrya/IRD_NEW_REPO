<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Enums\RoleEnum;
use App\Models\DailyTaskFailer;
use Illuminate\Console\Command;
use App\Enums\Type\StatusTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Enums\Type\DailyTaskTypeEnum;

class DailyTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-task';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->checkRegisterationExpiration();
    }
    protected function checkRegisterationExpiration()
    {
        $now = Carbon::now('UTC');
        DB::beginTransaction();
        try {
            // Fetch expired agreements in bulk using DB::table()
            $expiredAgreements = DB::table('agreements')
                ->where('end_date', '<', $now->toIso8601String())
                ->pluck('ngo_id'); // Fetch only ngo_id

            // Check if we have expired agreements
            if ($expiredAgreements->isNotEmpty()) {
                // Prepare data for bulk insert
                $statusData = $expiredAgreements->map(function ($ngo_id) use ($now) {
                    DB::table('ngo_statuses')
                        ->where('ngo_id', $ngo_id)
                        ->update(['is_active' => false]);
                    return [
                        'ngo_id' => $ngo_id,
                        'is_active' => 1,
                        'status_type_id' => StatusTypeEnum::registration_expired->value,
                        'comment' => "Added By System",
                        "user_id" => RoleEnum::super->value,
                        "created_at" => $now,
                        "updated_at" => $now,
                    ];
                })->toArray();

                DB::table('ngo_statuses')->insert($statusData);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('DailyTask Start ====================================================================');
            Log::info('DailyTask: ' . $e);
            Log::info('DailyTask End ====================================================================');

            DailyTaskFailer::create([
                'type' => DailyTaskTypeEnum::ngo_agreement_expiration_check->value,
                'error' => $e->getMessage()
            ]);
        }
    }
}
