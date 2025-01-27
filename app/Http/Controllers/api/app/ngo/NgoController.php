<?php

namespace App\Http\Controllers\api\app\ngo;

use App\Models\Ngo;
use App\Models\Email;
use App\Models\Address;
use App\Models\NgoTran;
use App\Enums\LanguageEnum;
use App\Enums\RoleEnum;
use App\Enums\StatusTypeEnum;
use App\Http\Requests\app\ngo\NgoProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\app\ngo\NgoRegisterRequest;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AddressTran;
use App\Models\NgoStatus;
use App\Models\StatusTypeTran;

class NgoController extends Controller
{


    public function ngos(Request $request, $page)
    {
        $perPage = $request->input('per_page', 10); // Number of records per page
        $page = $request->input('page', 1); // Current page
        $locale = App::getLocale();

        $query =  DB::table('ngos as n')
            ->join('ngo_trans as nt', 'nt.ngo_id', '=', 'n.id')
            ->where('nt.language_name', $locale)
            ->join('ngo_type_trans as ntt', 'ntt.ngo_type_id', '=', 'n.ngo_type_id')
            ->where('ntt.language_name', $locale)
            ->join('ngo_statuses as ns', 'ns.ngo_id', '=', 'n.id')
            ->join('status_type_trans as nstr', 'nstr.status_type_id', '=', 'ns.status_type_id')
            ->where('nstr.language_name', $locale)
            ->join('emails as e', 'e.id', '=', 'n.email_id')
            ->join('contacts as c', 'c.id', '=', 'n.contact_id')
            ->select(
                'n.id',
                'n.profile',
                'n.abbr',
                'n.registration_no',
                'n.date_of_establishment as establishment_date',
                'nstr.status_type_id as status_id',
                'nstr.name as status',
                'nt.name',
                'ntt.ngo_type_id  as type_id',
                'ntt.value as type',
                'e.value as email',
                'c.value as contact',
                'n.created_at'
            );


        $this->applyDate($query, $request);
        $this->applyFilters($query, $request);
        $this->applySearch($query, $request);

        $result = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'ngos' => $result
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function store(NgoRegisterRequest $request)
    {
        $validatedData = $request->validated();
        $locale = App::getLocale();
        // Begin transaction
        DB::beginTransaction();
        // Create email
        $email = Email::create(['value' => $validatedData['email']]);
        $contact = Contact::create(['value' => $validatedData['contact']]);
        // Create address
        $address = Address::create([
            'district_id' => $validatedData['district_id'],
            'province_id' => $validatedData['province_id'],
        ]);
        AddressTran::create([
            'address_id' => $address->id,
            'area' => $validatedData['area_english'],
            'language_name' =>  LanguageEnum::default->value,
        ]);
        AddressTran::create([
            'address_id' => $address->id,
            'area' => $validatedData['area_pashto'],
            'language_name' =>  LanguageEnum::pashto->value,
        ]);
        AddressTran::create([
            'address_id' => $address->id,
            'area' => $validatedData['area_farsi'],
            'language_name' =>  LanguageEnum::farsi->value,
        ]);
        // Create NGO
        $newNgo = Ngo::create([
            'abbr' => $validatedData['abbr'],
            'registration_no' => "",
            'role_id' => RoleEnum::ngo->value,
            'ngo_type_id' => $validatedData['ngo_type_id'],
            'address_id' => $address->id,
            'email_id' => $email->id,
            'username' => $request->username,
            'contact_id' => $contact->id,
            "password" => Hash::make($validatedData['password']),
        ]);

        // Crea a registration_no
        $newNgo->registration_no = "IRD" . '-' . Carbon::now()->year . '-' . $newNgo->id;
        $newNgo->save();
        // Set ngo status
        NgoStatus::create([
            "ngo_id" => $newNgo->id,
            "status_type_id" => StatusTypeEnum::not_logged_in->value,
            "comment" => "Newly Created"
        ]);
        NgoTran::create([
            'ngo_id' => $newNgo->id,
            'language_name' =>  LanguageEnum::default->value,
            'name' => $validatedData['name_english'],
        ]);

        NgoTran::create([
            'ngo_id' => $newNgo->id,
            'language_name' =>  LanguageEnum::farsi->value,
            'name' => $validatedData['name_farsi'],
        ]);
        NgoTran::create([
            'ngo_id' => $newNgo->id,
            'language_name' =>  LanguageEnum::pashto->value,
            'name' => $validatedData['name_pashto'],
        ]);

        $name =  $validatedData['name_english'];
        if ($locale == LanguageEnum::farsi->value) {
            $name = $validatedData['name_farsi'];
        } else if ($locale == LanguageEnum::pashto->value) {
            $name = $validatedData['name_pashto'];
        }
        // If everything goes well, commit the transaction
        DB::commit();

        $status = StatusTypeTran::where('status_type_id', StatusTypeEnum::not_logged_in->value)
            ->select('name')->first();
        return response()->json(
            [
                'message' => __('app_translation.success'),
                "ngo" => [
                    "id" => $newNgo->id,
                    "profile" => $newNgo->profile,
                    "abbr" => $newNgo->abbr,
                    "registration_no" => $newNgo->registration_no,
                    "status_id" => StatusTypeEnum::not_logged_in->value,
                    "status" => $status->name,
                    "type_id" => $validatedData['ngo_type_id'],
                    "establishment_date" => null,
                    "name" => $name,
                    "contact" => $validatedData['contact'],
                    "email" => $validatedData['email'],
                    "created_at" => $newNgo->created_at,
                ]
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    public function ngo($id)
    {
        $locale = App::getLocale();

        return response()->json(
            [
                'message' => __('app_translation.success'),
                "ngo" => []
            ],
            200,
            [],
            JSON_UNESCAPED_UNICODE
        );
    }
    public function profileUpdate(NgoProfileUpdateRequest $request, $id)
    {


        // Find the NGO
        $ngo = Ngo::find($id);

        if (!$ngo || $ngo->is_editable != 1) {
            return response()->json(['message' => __('app_translation.notEditable')], 403);
        }


        $validatedData = $request->validated();


        // Begin transaction
        DB::beginTransaction();

        $path = $this->storeProfile($request, 'ngo-profile');
        $ngo->update([
            "profile" =>  $path,
        ]);

        // Update default language record
        $ngoTran = NgoTran::where('ngo_id', $id)
            ->where('language_name', LanguageEnum::default->value)
            ->first();

        if ($ngoTran) {
            $ngoTran->update([
                'name' => $validatedData['name_english'],
                'vision' => $validatedData['vision_english'],
                'mission' => $validatedData['mission_english'],
                'general_objective' => $validatedData['general_objective_english'],
                'objective' => $validatedData['objective_english'],
                'introduction' => $validatedData['introduction_english']
            ]);
        } else {
            return response()->json(['message' => __('app_translation.not_found')], 404);
        }

        // Manage multilingual NgoTran records
        $languages = [
            'pashto',
            'farsi'

        ];

        NgoTran::create([
            'ngo_id' => $id,
            'language_name ' => 'ps',
            'name' => $validatedData["name_pashto"],
            'vision' => $validatedData["vision_pashto"],
            'mission' => $validatedData["mission_pashto"],
            'general_objective' => $validatedData["general_objective_pashto"],
            'objective' => $validatedData["objective_pashto"],
            'introduction' => $validatedData["introduction_pashto"]

        ]);
        NgoTran::create([
            'ngo_id' => $id,
            'language_name ' => 'fa',
            'name' => $validatedData["name_farsi"],
            'vision' => $validatedData["vision_farsi"],
            'mission' => $validatedData["mission_farsi"],
            'general_objective' => $validatedData["general_objective_farsi"],
            'objective' => $validatedData["objective_farsi"],
            'introduction' => $validatedData["introduction_farsi"]

        ]);



        // Instantiate DirectorController and call its store method
        $directorController = new \App\Http\Controllers\api\app\director\DirectorController();
        $directorController->store($request, $id);

        // store document
        // Commit transaction
        DB::commit();
        return response()->json(['message' => __('app_translation.success')], 200);
    }
    public function ngoCount()
    {
        $statistics = DB::select("
        SELECT
         COUNT(*) AS count,
            (SELECT COUNT(*) FROM ngos WHERE DATE(created_at) = CURDATE()) AS todayCount,
            (SELECT COUNT(*) FROM ngos n JOIN ngo_statuses ns ON n.id = ns.ngo_id WHERE ns.status_type_id = ?) AS activeCount,
         (SELECT COUNT(*) FROM ngos n JOIN ngo_statuses ns ON n.id = ns.ngo_id WHERE ns.status_type_id = ?) AS unRegisteredCount
        FROM ngos
            ", [StatusTypeEnum::active->value, StatusTypeEnum::unregistered->value]);
        return response()->json([
            'counts' => [
                "count" => $statistics[0]->count,
                "todayCount" => $statistics[0]->todayCount,
                "activeCount" => $statistics[0]->activeCount,
                "unRegisteredCount" =>  $statistics[0]->unRegisteredCount
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    // date function 
    protected function applyDate($query, $request)
    {
        // Apply date filtering conditionally if provided
        $startDate = $request->input('filters.date.startDate');
        $endDate = $request->input('filters.date.endDate');

        if ($startDate) {
            $query->where('n.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('n.created_at', '<=', $endDate);
        }
    }
    // search function 
    protected function applySearch($query, $request)
    {

        $searchColumn = $request->input('filters.search.column');
        $searchValue = $request->input('filters.search.value');

        if ($searchColumn && $searchValue) {
            $allowedColumns = ['title', 'contents'];

            // Ensure that the search column is allowed
            if (in_array($searchColumn, $allowedColumns)) {
                $query->where($searchColumn, 'like', '%' . $searchValue . '%');
            }
        }
    }
    // filter function
    protected function applyFilters($query, $request)
    {
        $sort = $request->input('filters.sort'); // Sorting column
        $order = $request->input('filters.order', 'asc'); // Sorting order (default 

        if ($sort && in_array($sort, ['id', 'name', 'type', 'contact', 'status'])) {
            $query->orderBy($sort, $order);
        } else {
            // Default sorting if no sort is provided
            $query->orderBy("created_at", 'desc');
        }
    }
}
