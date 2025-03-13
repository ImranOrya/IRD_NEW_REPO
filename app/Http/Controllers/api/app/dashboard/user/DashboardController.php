<?php

namespace App\Http\Controllers\api\app\dashboard\user;

use App\Models\NgoTypeTrans;
use Illuminate\Http\Request;

use Morilog\Jalali\Jalalian;
use App\Models\StatusTypeTran;
use Illuminate\Support\Carbon;
use App\Enums\Type\NgoTypeEnum;
use Symfony\Component\Clock\now;
use App\Enums\Type\StatusTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Returns header data with counts of NGOs based on type and status.
     * 
     * @return array
     */
    public function headerData()
    {

        $countsByStatus = $this->ngoCountByStatus();

        return [

            'ngo_status' => [
                'registerFormNotComplete' => $countsByStatus['register_form_not_complete'],
                'blocked' => $countsByStatus['blocked'],
                'registerFormCompleted' => $countsByStatus['register_form_completed'],
                'signedRegisterFormSubmitted' => $countsByStatus['signed_register_form_submitted'],
                'totalNgo' => $countsByStatus['total_ngo']
            ]


        ];
    }
    public function ngoCountByNgoType()
    {

        $countsByType = $this->ngoCountByTypes();

        return [
            'ngo_type' => [
                'internationalNgoCount' => $countsByType['international_count'],
                'domesticNgoCount' => $countsByType['domestic_count'],
                'intergovernmentalNgoCount' => $countsByType['intergovernmental_count'],
                'totalNgoCount' => $countsByType['total_register_count']
            ]
        ];
    }

    /**
     * Get the count of NGOs by type.
     * 
     * @return array
     */
    private function ngoCountByTypes()
    {
        $cacheKey = 'ngo_count_all_types';

        // Check if the counts are already cached
        $counts = Cache::remember($cacheKey, 180, function () {
            return DB::table('ngos')
                ->join('ngo_statuses', 'ngos.id', '=', 'ngo_statuses.ngo_id')
                ->selectRaw('
                    SUM(ngo_type_id = ?) as international_count,
                    SUM(ngo_type_id = ?) as domestic_count,
                    SUM(ngo_type_id = ?) as intergovernmental_count
                ', [
                    NgoTypeEnum::International->value,
                    NgoTypeEnum::Domestic->value,
                    NgoTypeEnum::Intergovernmental->value
                ])
                ->where('ngo_statuses.is_active', 1)
                ->where('ngo_statuses.status_type_id', StatusTypeEnum::registered->value)
                ->first();
        });

        $ngoTypeTrans = $this->getNgoTypeTrans();

        // Map status translations into a key-value format for easy lookup
        $typeTransMap = $ngoTypeTrans->pluck('name', 'ngo_type_id')->toArray();

        return [
            'international_count' => ['name' => $typeTransMap[NgoTypeEnum::International->value], 'count' => $counts->international_count ?? 0],
            'domestic_count' => ['name' => $typeTransMap[NgoTypeEnum::Domestic->value], 'count' => $counts->domestic_count ?? 0],
            'intergovernmental_count' => ['name' => $typeTransMap[NgoTypeEnum::Intergovernmental->value], 'count' => $counts->intergovernmental_count ?? 0],
            'total_register_count' => $counts->international_count + $counts->domestic_count + $counts->intergovernmental_count
        ];
    }

    /**
     * Get the count of NGOs by status.
     * 
     * @return array
     */
    private function ngoCountByStatus()
    {
        $cacheKey = 'ngo_count_all_status_types';

        // Check if the counts are already cached
        $counts = Cache::remember($cacheKey, 180, function () {
            return DB::table('ngo_statuses')
                ->selectRaw('
                    SUM(status_type_id = ?) as register_form_not_complete,
                    SUM(status_type_id = ?) as blocked,
                    SUM(status_type_id = ?) as register_form_completed,
                    SUM(status_type_id = ?) as registered,
                    SUM(status_type_id = ?) as signed_register_form_submitted
                ', [
                    StatusTypeEnum::register_form_not_completed->value,
                    StatusTypeEnum::blocked->value,
                    StatusTypeEnum::register_form_completed->value,
                    StatusTypeEnum::registered->value,
                    StatusTypeEnum::signed_register_form_submitted->value
                ])
                ->where('is_active', 1)
                ->first();
        });

        $statusTrans = $this->getStatusTrans();

        // Map status translations into a key-value format for easy lookup
        $statusTransMap = $statusTrans->pluck('name', 'status_type_id')->toArray();

        return [
            'registered' => [
                'name' => $statusTransMap[StatusTypeEnum::registered->value] ?? 'Registered',
                'count' => $counts->registered ?? 0
            ],
            'blocked' => [
                'name' => $statusTransMap[StatusTypeEnum::blocked->value] ?? 'Blocked',
                'count' => $counts->blocked ?? 0
            ],
            'register_form_not_complete' => [
                'name' => $statusTransMap[StatusTypeEnum::register_form_not_completed->value] ?? 'Register Form Not Completed',
                'count' => $counts->register_form_not_complete ?? 0
            ],
            'register_form_completed' => [
                'name' => $statusTransMap[StatusTypeEnum::register_form_completed->value] ?? 'Register Form Completed',
                'count' => $counts->register_form_completed ?? 0
            ],
            'signed_register_form_submitted' => [
                'name' => $statusTransMap[StatusTypeEnum::signed_register_form_submitted->value] ?? 'Signed Register Form Submitted',
                'count' => $counts->signed_register_form_submitted ?? 0
            ],
            'total_ngo' => (
                $counts->registered +
                $counts->blocked +
                $counts->register_form_not_complete +
                $counts->register_form_completed +
                $counts->signed_register_form_submitted
            )
        ];
    }
    public function ngoCountByTypesLastSixMonths()
    {
        $locale =  app()->getLocale();
        $cacheKey = 'ngo_count_types_chart_last_six_months' . $locale;

        // Cache the data for 3 hours or 180 minutes
        $data = Cache::remember($cacheKey, 180, function () use ($locale) {
            // Prepare date range once
            $startDate = now()->subMonths(5)->startOfMonth();
            $endDate = now()->endOfMonth();

            // Get the translations only once
            $ngoTypeTrans = $this->getNgoTypeTrans()->pluck('name', 'ngo_type_id')->toArray();

            // Precompute all results
            $results = DB::table('ngos')
                ->join('ngo_statuses', 'ngos.id', '=', 'ngo_statuses.ngo_id')
                ->selectRaw('
                    MONTH(ngo_statuses.created_at) as month,
                    SUM(ngo_type_id = ?) as international_count,
                    SUM(ngo_type_id = ?) as domestic_count,
                    SUM(ngo_type_id = ?) as intergovernmental_count
                ', [
                    NgoTypeEnum::International->value,
                    NgoTypeEnum::Domestic->value,
                    NgoTypeEnum::Intergovernmental->value
                ])
                ->where('ngo_statuses.is_active', 1)
                ->where('ngo_statuses.status_type_id', StatusTypeEnum::registered->value)
                ->whereBetween('ngo_statuses.created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('MONTH(ngo_statuses.created_at)'))
                ->get()
                ->keyBy('month')
                ->toArray();

            // Precompute zodiac names for Jalali conversion
            $useJalaliConversion = in_array($locale, ['fa', 'ps']);
            $zodiacNames = [
                1 => 'حمل',
                2 => 'ثور',
                3 => 'جوزا',
                4 => 'سرطان',
                5 => 'اسد',
                6 => 'سنبله',
                7 => 'میزان',
                8 => 'عقرب',
                9 => 'قوس',
                10 => 'جدی',
                11 => 'دلو',
                12 => 'حوت',
            ];

            $finalData = [];

            // Loop through the last 6 months and avoid recalculating the current date multiple times
            for ($i = 0; $i < 6; $i++) {
                $monthDate = now()->subMonths(5 - $i);
                $gregorianMonthNum = (int) $monthDate->format('n');
                $gregorianMonthName = $monthDate->locale($locale)->translatedFormat('F');

                // Use Jalali conversion if required
                $monthname = $gregorianMonthName;
                if ($useJalaliConversion) {
                    $jalaliDate = Jalalian::fromCarbon($monthDate);
                    $monthname = $zodiacNames[$jalaliDate->getMonth()] ?? $gregorianMonthName;
                }

                // Fetch counts for the month (using null coalescing to ensure zero if not set)
                $counts = $results[$gregorianMonthNum] ?? null;
                $internationalCount = $counts->international_count ?? 0;
                $domesticCount = $counts->domestic_count ?? 0;
                $intergovernmentalCount = $counts->intergovernmental_count ?? 0;

                $finalData[] = [
                    'month' => $monthname,
                    'International' => $internationalCount,
                    'Domestic' => $domesticCount,
                    'Intergovernmental' => $intergovernmentalCount,
                ];
            }

            // Return the final data with translations
            return [
                'data' => $finalData,
                'translations' => [
                    'international' => $ngoTypeTrans[NgoTypeEnum::International->value] ?? 'International',
                    'domestic' => $ngoTypeTrans[NgoTypeEnum::Domestic->value] ?? 'Domestic',
                    'intergovernmental' => $ngoTypeTrans[NgoTypeEnum::Intergovernmental->value] ?? 'Intergovernmental',
                ],
            ];
        });

        return $data;
    }
}
