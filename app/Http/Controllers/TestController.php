<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Ngo;
use App\Models\News;
use App\Models\Role;
use App\Models\User;

use App\Models\Audit;
use App\Models\Email;
use App\Models\Staff;

use App\Models\Gender;
use App\Enums\RoleEnum;
use App\Models\Address;
use App\Models\Country;
use App\Models\NgoTran;
use App\Enums\StaffEnum;
use App\Models\Approval;
use App\Models\Currency;
use App\Models\Director;
use App\Models\District;
use App\Models\Document;
use App\Models\Province;
use App\Models\Agreement;
use App\Models\CheckList;
use App\Models\Translate;
use App\Models\StatusType;
use App\Enums\LanguageEnum;
use App\Models\AddressTran;
use App\Models\PendingTask;
use App\Models\CurrencyTran;
use App\Models\NidTypeTrans;
use Illuminate\Http\Request;
use App\Enums\PermissionEnum;
use App\Models\CheckListType;
use Sway\Models\RefreshToken;
use App\Models\RolePermission;
use App\Models\StatusTypeTran;
use App\Models\UserPermission;
use App\Enums\CheckListTypeEnum;
use App\Enums\Status\StatusEnum;
use App\Enums\SubPermissionEnum;
use App\Models\RolePermissionSub;
use App\Enums\DestinationTypeEnum;
use App\Enums\Type\StatusTypeEnum;

use App\Models\PendingTaskContent;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use App\Models\PendingTaskDocument;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Enums\Type\ApprovalTypeEnum;
use App\Traits\Address\AddressTrait;
use function Laravel\Prompts\select;
use Illuminate\Support\Facades\Http;
use App\Enums\CheckList\CheckListEnum;
use Illuminate\Support\Facades\Schema;
use App\Enums\Type\RepresenterTypeEnum;
use App\Enums\Type\RepresentorTypeEnum;
use App\Repositories\ngo\NgoRepositoryInterface;
use App\Repositories\User\UserRepositoryInterface;

class TestController extends Controller
{
    protected $ngoRepository;
    protected $userRepository;
    use HelperTrait;

    private function detectDevice($userAgent)
    {
        if (str_contains($userAgent, 'Windows')) return 'Windows PC';
        if (str_contains($userAgent, 'Macintosh')) return 'Mac';
        if (str_contains($userAgent, 'iPhone')) return 'iPhone';
        if (str_contains($userAgent, 'Android')) return 'Android Device';
        return 'Unknown Device';
    }

    private function getLocationFromIP($ip)
    {
        try {
            $response = Http::get("http://ip-api.com/json/{$ip}");
            return $response->json()['city'] . ', ' . $response->json()['country'];
        } catch (\Exception $e) {
            return 'Unknown Location';
        }
    }
    public function __construct(
        NgoRepositoryInterface $ngoRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->ngoRepository = $ngoRepository;
        $this->userRepository = $userRepository;
    }
    use AddressTrait;

    public function format($approvals)
    {
        return $approvals->groupBy('id')->map(function ($group) {
            $docs = $group->filter(function ($item) {
                return $item->approval_document_id !== null;
            });

            $approval = $group->first();

            $approval->approved = (bool) $approval->approved;
            if ($docs->isNotEmpty()) {
                $docs->documents = $docs->map(function ($doc) {
                    return [
                        'id' => $doc->approval_document_id,
                        'documentable_id' => $doc->documentable_id,
                        'documentable_type' => $doc->documentable_type,
                    ];
                });
            } else {
                $approval->documents = [];
            }
            unset($approval->approval_document_id);

            return $approval;
        })->values();
    }
    function extractDeviceInfo($userAgent)
    {
        // Match OS and architecture details
        if (preg_match('/\(([^)]+)\)/', $userAgent, $matches)) {
            return $matches[1]; // Extract content inside parentheses
        }
        return "Unknown Device";
    }
    function extractBrowserInfo($userAgent)
    {
        // Match major browsers (Chrome, Firefox, Safari, Edge, Opera, etc.)
        if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera|OPR|MSIE|Trident)[\/ ]([\d.]+)/', $userAgent, $matches)) {
            $browser = $matches[1];
            $version = $matches[2];

            // Fix for Opera (uses "OPR" in User-Agent)
            if ($browser == 'OPR') {
                $browser = 'Opera';
            }

            // Fix for Internet Explorer (uses "Trident" in newer versions)
            if ($browser == 'Trident') {
                preg_match('/rv:([\d.]+)/', $userAgent, $rvMatches);
                $version = $rvMatches[1] ?? $version;
                $browser = 'Internet Explorer';
            }

            return "$browser $version";
        }

        return "Unknown Browser";
    }
    public function currency()
    {
        $currencies = [
            [
                'abbr' => 'AFN',
                'symbol' => '؋',
                'translations' => [
                    'en' => 'Afghani',
                    'ps' => 'افغانی',
                    'fa' => 'افغانی',
                ],
            ],
            [
                'abbr' => 'USD',
                'symbol' => '$',
                'translations' => [
                    'en' => 'US Dollar',
                    'ps' => 'ډالر',
                    'fa' => 'دالر',
                ],
            ],
            [
                'abbr' => 'EUR',
                'symbol' => '€',
                'translations' => [
                    'en' => 'Euro',
                    'ps' => 'یورو',
                    'fa' => 'یورو',
                ],
            ],
            [
                'abbr' => 'GBP',
                'symbol' => '£',
                'translations' => [
                    'en' => 'Pound',
                    'ps' => 'پوند',
                    'fa' => 'پوند',
                ],
            ],
        ];

        foreach ($currencies as $currency) {
            $curr = Currency::create([
                'abbr' => $currency['abbr'],
                'symbol' => $currency['symbol'],
            ]);

            foreach ($currency['translations'] as $lang => $value) {
                CurrencyTran::create([
                    'currency_id' => $curr->id,
                    'name' => $value,
                    'language_name' => $lang,
                ]);
            }
        }
    }
    public function index(Request $request)
    {
        $locale = App::getLocale();
        $directors = DB::table('directors as d')
            ->where('d.id', 2)
            ->join('director_trans as dt', function ($join) use ($locale) {
                $join->on('dt.director_id', '=', 'd.id')
                    ->where('dt.language_name', '=', $locale);
            })
            ->join('contacts as c', 'd.contact_id', '=', 'c.id')
            ->join('emails as e', 'd.email_id', '=', 'e.id')
            ->select(
                'd.id',
                'd.is_active',
                'dt.name',
                'dt.last_name as surname',
                'c.value as contact',
                'e.value as email',
            )
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'message' => __('app_translation.success'),
            'directors' => $directors,
        ], 200, [], JSON_UNESCAPED_UNICODE);


        $locale = App::getLocale();
        $includes = [StatusEnum::block->value];
        $statuses = DB::table('statuses as st')
            ->whereIn('st.id', $includes)
            ->leftjoin('status_trans as stt', function ($join) use ($locale) {
                $join->on('stt.status_id', '=', 'st.id')
                    ->where('stt.language_name', $locale);
            })
            ->select('st.id', 'stt.name')->get();

        return response()->json($statuses);
        $this->currency();
    }
}
