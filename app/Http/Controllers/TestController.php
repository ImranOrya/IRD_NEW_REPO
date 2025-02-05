<?php

namespace App\Http\Controllers;

use App\Models\Ngo;
use App\Models\News;
use App\Models\User;
use App\Models\Staff;

use App\Models\Gender;
use App\Models\Address;
use App\Models\Country;

use App\Enums\StaffEnum;
use App\Models\Director;
use App\Models\Province;
use App\Models\CheckList;
use App\Models\Translate;
use App\Enums\LanguageEnum;
use App\Models\NidTypeTrans;
use Illuminate\Http\Request;
use App\Enums\Type\StatusTypeEnum;
use App\Models\PendingTaskContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Traits\Address\AddressTrait;

class TestController extends Controller
{
    use AddressTrait;
    public function index(Request $request)
    {
        $path = storage_path() . "/app/temp/c9424391-b967-4dbf-a3c3-747f6d8382a2.pdf";
        return dd(file_exists($path));
        return PendingTaskContent::where('pending_task_id', 2)
            ->select('content', 'id')
            ->orderBy('id', 'desc')
            ->first();
        $locale = App::getLocale();
        $query = DB::table('staff as s')
            ->where('staff_type_id', StaffEnum::manager->value)
            ->join('staff_trans as st', function ($join) use ($locale) {
                $join->on('st.staff_id', '=', 's.id')
                    ->where('st.language_name', '=', $locale);
            })
            ->select(
                's.id',
                's.contact',
                's.email',
                's.profile as picture',
                'st.name'
            )
            ->first();
        return $query;


        $ngo_id = 1;
        return DB::table('ngos as n')
            ->join('ngo_type_trans as ntt', 'ntt.ngo_type_id', '=', 'n.ngo_type_id')  // Join the ngo_type_trans table
            ->leftJoin('addresses as ad', 'ad.id', '=', 'n.address_id')
            ->leftJoin('address_trans as adt', function ($join) use ($locale) {
                $join->on('ad.id', '=', 'adt.address_id')
                    ->where('adt.language_name', '=', $locale);
            })
            ->leftJoin('emails as em', 'em.id', '=', 'n.email_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'n.contact_id')
            ->where('n.id', $ngo_id)
            ->select(
                'n.id',
                'em.value',
                'c.value',
                DB::raw("MAX(CASE WHEN ntt.language_name = 'en' THEN ntt.value END) as name_english"),  // English translation
                DB::raw("MAX(CASE WHEN ntt.language_name = 'fa' THEN ntt.value END) as name_farsi"),   // Farsi translation
                DB::raw("MAX(CASE WHEN ntt.language_name = 'ps' THEN ntt.value END) as name_pashto")   // Pashto translation
            )
            ->groupBy('n.id', 'em.value', 'c.value')
            ->first();


        return CheckList::join('check_list_trans as ct', 'ct.check_list_id', '=', 'check_lists.id')
            ->where('ct.language_name', $locale)
            ->select('ct.value as name', 'check_lists.id', 'check_lists.file_extensions', 'check_lists.description')
            ->orderBy('check_lists.id', 'desc')
            ->get();


        return   $this->getCompleteAddress(1, 'fa');
        $lang = 'en';
        $id = 1;

        $irdDirector = Staff::with([
            'staffTran' => function ($query) use ($lang) {
                $query->select('staff_id', 'name', 'last_name')->where('language_name', $lang);
            }
        ])->select('id')->where('staff_type_id', StaffEnum::director->value)->first();


        return $irdDirector->staffTran[0]->name . '  ' . $irdDirector->staffTran[0]->last_name;

        $lang = 'en';
        $ngo = Ngo::with(
            [
                'ngoTrans' => function ($query) use ($lang) {
                    $query->select('ngo_id', 'name', 'vision', 'mission', 'general_objective', 'objective')->where('language_name', $lang);
                },
                'email:id,value',
                'contact:id,value',


            ]

        )->select(
            'id',
            'email_id',
            'contact_id',
            'address_id',
            'abbr',
            'registration_no',
            'date_of_establishment',
            'moe_registration_no',

        )->where('id', 1)->first();

        return    $this->getCompleteAddress($ngo->address_id, 'en');

        dd($query->toSql(), $query->getBindings());
        // ->get();

        // ->join('')
        $query = DB::table('news AS n')
            // Join for news translations (title, contents)
            ->join('news_trans AS ntr', function ($join) use ($locale) {
                $join->on('ntr.news_id', '=', 'n.id')
                    ->where('ntr.language_name', '=', $locale); // Filter by language
            })
            // Join for news type translations
            ->join('news_type_trans AS ntt', function ($join) use ($locale) {
                $join->on('ntt.news_type_id', '=', 'n.news_type_id')
                    ->where('ntt.language_name', '=', $locale); // Filter by language
            })
            // Join for priority translations
            ->join('priority_trans AS pt', function ($join) use ($locale) {
                $join->on('pt.priority_id', '=', 'n.priority_id')
                    ->where('pt.language_name', '=', $locale); // Filter by language
            })
            // Join for user (assuming the `users` table has the `username` field)
            ->join('users AS u', 'u.id', '=', 'n.user_id')
            // Left join for documents (to get all documents related to the news)
            ->leftJoin('news_documents AS nd', 'nd.news_id', '=', 'n.id')
            // Select required fields from all tables
            ->select(
                'n.id',
                'n.visible',
                'n.date',
                'n.visibility_date',
                'n.news_type_id',
                'ntt.value AS news_type',
                'n.priority_id',
                'pt.value AS priority',
                'u.username AS user',
                'ntr.title',
                'ntr.contents',
                'nd.url AS image'  // Assuming you want the first image URL
            )
            // Get the data
            ->get();

        return $query;

        // $query  = DB::table('news AS n')
        //     ->leftJoin('news_trans AS ntr', function ($join) use ($locale) {
        //         $join->on('ntr.news_id', '=', 'n.id')
        //             ->where('ntr.language_name', '=', $locale);
        //     })
        //     ->leftJoin('news_type_trans AS ntt', function ($join) use ($locale) {
        //         $join->on('ntt.news_type_id', '=', 'n.news_type_id')
        //             ->where('ntt.language_name', '=', $locale);
        //     })
        //     ->leftJoin('priority_trans AS pt', function ($join) use ($locale) {
        //         $join->on('pt.priority_id', '=', 'n.priority_id')
        //             ->where('pt.language_name', '=', $locale);
        //     })
        //     ->leftJoin('users AS u', function ($join) {
        //         $join->on('u.id', '=', 'n.user_id');
        //     })
        //     ->leftJoin('news_documents AS nd', 'nd.news_id', '=', 'n.id')
        //     ->distinct()
        //     ->select(
        //         // 'n.id',
        //         // "n.visible",
        //         // "date",
        //         // "visibility_date",
        //         // 'n.news_type_id',
        //         // 'ntt.value AS news_type',
        //         // 'n.priority_id',
        //         // 'pt.value AS priority',
        //         // 'u.username AS user',
        //         // 'ntr.title',
        //         // 'ntr.contents',
        //         // 'nd.url as image',
        //     )
        //     ->get();


        return $query;


        $ngoId = 2;
        $user = DB::table('ngos AS n')
            ->where('n.id', '=', $ngoId)
            ->join('ngo_trans AS ntr', function ($join) use ($locale) {
                $join->on('ntr.ngo_id', '=', 'n.id');
                // ->where('ntr.language_name', '=', $locale);
            })
            ->join('ngo_statuses AS ns', function ($join) {
                $join->on('ns.ngo_id', '=', 'n.id');
            })
            ->join('status_type_trans AS nst', function ($join) use ($locale) {
                $join->on('nst.status_type_id', '=', 'ns.status_type_id')
                    ->where('nst.language_name', '=', $locale);
            })
            ->join('ngo_types AS nt', 'n.ngo_type_id', '=', 'nt.id')
            ->join('ngo_type_trans AS ntt', function ($join) use ($locale) {
                $join->on('ntt.ngo_type_id', '=', 'nt.id')
                    ->where('nst.language_name', '=', $locale);
            })
            ->join('emails as e', 'n.email_id', '=', 'e.id')
            ->join('contacts as c', 'n.contact_id', '=', 'c.id')
            ->join('roles as r', 'n.role_id', '=', 'r.id')
            ->select(
                'n.id',
                'n.abbr',
                'n.registration_no',
                'n.address_id',
                'n.username',
                'ntr.name AS name',
                'ns.id AS status_id',
                'nst.name AS status_name',
                'n.ngo_type_id',
                'ntt.value AS ngo_type_name',
                'n.role_id',
                'r.name AS role',
                'n.email_id',
                'e.value AS email',
                'n.contact_id',
                'c.value AS contact',
            )
            ->first();
        return $user;
        $ngos = DB::select("
        SELECT
         COUNT(*) AS count,
            (SELECT COUNT(*) FROM ngos WHERE DATE(created_at) = CURDATE()) AS todayCount,
            (SELECT COUNT(*) FROM ngos n JOIN ngo_statuses ns ON n.id = ns.ngo_id WHERE ns.status_type_id = ?) AS activeCount,
         (SELECT COUNT(*) FROM ngos n JOIN ngo_statuses ns ON n.id = ns.ngo_id WHERE ns.status_type_id = ?) AS unRegisteredCount
        FROM ngos
    ", [StatusTypeEnum::active->value, StatusTypeEnum::unregistered->value]);
        return $ngos;
        return $statistics[0]->todayCount;

        $users = User::with([
            'contact' => function ($query) {
                $query->select('id', 'value'); // Load contact value
            },
            'email' => function ($query) {
                $query->select('id', 'value'); // Load email value
            },
            'destinationThrough' => function ($query) {
                $query->select('translable_id', 'value as destination')
                    ->where('translable_type', 'App\\Models\\Destination')
                    ->where('language_name', 'fa')
                    ->groupBy('translable_id');
            },
            'jobThrough' => function ($query) {
                $query->select('translable_id', 'value as job')
                    ->where('translable_type', 'App\\Models\\ModelJob')
                    ->where('language_name', 'fa')
                    ->groupBy('translable_id');
            }
        ])->get();
    }
}
