<?php

namespace App\Http\Controllers\api\template;

use App\Http\Controllers\Controller;
use App\Http\Requests\template\office\OfficeStoreRequest;
use App\Http\Requests\template\office\OfficeUpdateRequest;
use App\Http\Requests\template\office\StaffStoreRequest;
use App\Http\Requests\template\office\StaffUpdateRequest;
use App\Models\OfficeInformation;
use App\Models\Staff;
use App\Enums\StaffEnum;
use App\Models\StaffTran;
use App\Enums\LanguageEnum;
use Illuminate\Support\Facades\DB;

class AboutController extends Controller
{
    public function about()
    {
        $manager = $this->manager();
        $director = $this->director();
        $technicalSupports = $this->technicalSupports();

        return response()->json([
            "manager" => $manager,
            "director" => $director,
            "technicalSupports" => $technicalSupports,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function manager()
    {
        $query = DB::table('staff as s')
            ->join('staff_trans as st', 'st.staff_id', '=', 's.id')
            ->where('staff_type_id', StaffEnum::manager->value)
            ->select(
                's.id',
                's.staff_type_id',
                's.contact',
                's.email',
                's.profile as picture',
                's.created_at',
                's.updated_at',
                DB::raw("MAX(CASE WHEN st.language_name = 'en' THEN st.name END) as name_english"),
                DB::raw("MAX(CASE WHEN st.language_name = 'fa' THEN st.name END) as name_farsi"),
                DB::raw("MAX(CASE WHEN st.language_name = 'ps' THEN st.name END) as name_pashto")
            )
            ->groupBy('s.id', 's.staff_type_id', 's.contact', 's.email', 's.profile', 's.created_at', 's.updated_at')
            ->first();
        return response()->json([
            "manager" => $query,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function director()
    {
        $query = DB::table('staff as s')
            ->join('staff_trans as st', 'st.staff_id', '=', 's.id')
            ->where('staff_type_id', StaffEnum::director->value)
            ->select(
                's.id',
                's.staff_type_id',
                's.contact',
                's.email',
                's.profile as picture',
                's.created_at',
                's.updated_at',
                DB::raw("MAX(CASE WHEN st.language_name = 'en' THEN st.name END) as name_english"),
                DB::raw("MAX(CASE WHEN st.language_name = 'fa' THEN st.name END) as name_farsi"),
                DB::raw("MAX(CASE WHEN st.language_name = 'ps' THEN st.name END) as name_pashto")
            )
            ->groupBy('s.id', 's.staff_type_id', 's.contact', 's.email', 's.profile', 's.created_at', 's.updated_at')
            ->first();
        return response()->json([
            "director" => $query,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function technicalSupports()
    {
        $query = DB::table('staff as s')
            ->join('staff_trans as st', 'st.staff_id', '=', 's.id')
            ->where('staff_type_id', StaffEnum::technical_support->value)
            ->select(
                's.id',
                's.staff_type_id',
                's.contact',
                's.email',
                's.profile as picture',
                's.created_at',
                's.updated_at',
                DB::raw("MAX(CASE WHEN st.language_name = 'en' THEN st.name END) as name_english"),
                DB::raw("MAX(CASE WHEN st.language_name = 'fa' THEN st.name END) as name_farsi"),
                DB::raw("MAX(CASE WHEN st.language_name = 'ps' THEN st.name END) as name_pashto")
            )
            ->groupBy('s.id', 's.staff_type_id', 's.contact', 's.email', 's.profile', 's.created_at', 's.updated_at')
            ->get();
        return response()->json([
            "technicalStaff" => $query,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function office()
    {
        $query = OfficeInformation::select()->first();
        if ($query) {
            return response()->json([
                "office" => [
                    "id" => $query->id,
                    "address_english" => $query->address_english,
                    "address_farsi" => $query->address_farsi,
                    "address_pashto" => $query->address_pashto,
                    "contact" => $query->contact,
                    "email" => $query->email,
                ],

            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->json([
            "office" => null,

        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function destroy($id)
    {
        $staff = Staff::find($id);
        if ($staff) {
            // Begin transaction
            DB::beginTransaction();
            // 1. Delete Translation
            StaffTran::where('staff_id', $staff->id)->delete();
            // Delete documents
            $path = storage_path('app/' . $staff->profile);

            if (file_exists($path)) {
                unlink($path);
            }
            $staff->delete();

            // Commit transaction
            DB::commit();
            return response()->json([
                'message' => __('app_translation.success'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else
            return response()->json([
                'message' => __('app_translation.failed'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
    }

    public function staffStore(StaffStoreRequest $request)
    {
        // Validate the request
        $validateData = $request->validated(); // Use validated() for already validated data
        // Begin transaction
        DB::beginTransaction();
        // Store the profile
        $profile = $this->storeProfile($request, 'staff-profile', "picture");

        // Store Staff data
        $staff = Staff::create([
            'contact' => $validateData['contact'],
            'email' => $validateData['email'],
            'staff_type_id' => $validateData['staff_type_id'],
            'profile' => $profile,
        ]);

        // Handle translation insertion
        StaffTran::create([
            'language_name' => LanguageEnum::default->value,
            'staff_id' => $staff->id,
            'name' => $validateData["name_english"],
        ]);
        StaffTran::create([
            'language_name' => LanguageEnum::farsi->value,
            'staff_id' => $staff->id,
            'name' => $validateData["name_farsi"],
        ]);
        StaffTran::create([
            'language_name' => LanguageEnum::pashto->value,
            'staff_id' => $staff->id,
            'name' => $validateData["name_pashto"],
        ]);
        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
            'staff' => [
                "id" => $staff->id,
                "name_english" => $validateData['name_english'],
                "name_pashto" => $validateData['name_pashto'],
                "name_farsi" => $validateData['name_farsi'],
                "contact" => $validateData['contact'],
                "email" => $validateData['email'],
                "picture" => $profile,
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function officeStore(OfficeStoreRequest $request)
    {
        // Validate the request
        $validateData = $request->validated();
        // Begin transaction
        DB::beginTransaction();

        // Store Staff data
        $office = OfficeInformation::create([
            'contact' => $validateData['contact'],
            'email' => $validateData['email'],
            'address_english' => $validateData['address_english'],
            'address_farsi' => $validateData['address_farsi'],
            'address_pashto' => $validateData['address_pashto'],
        ]);

        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
            'office' => [
                "id" => $office->id,
                "address_english" => $validateData['address_english'],
                "address_farsi" => $validateData['address_farsi'],
                "address_pashto" => $validateData['address_pashto'],
                "contact" => $validateData['contact'],
                "email" => $validateData['email'],
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function officeUpdate(OfficeUpdateRequest $request)
    {
        // Validate the request
        $validateData = $request->validated(); // Use validated() for already validated data
        // Begin transaction
        DB::beginTransaction();
        // Find the staff entry by ID
        $office = OfficeInformation::find($validateData['id']);
        if (!$office) {
            return response()->json([
                'message' => __('app_translation.not_found'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // Update Staff details
        $office->contact = $validateData['contact'];
        $office->email = $validateData['email'];
        $office->address_english = $validateData['address_english'];
        $office->address_pashto = $validateData['address_pashto'];
        $office->email = $validateData['email'];
        $office->save();

        // Commit transaction
        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
            'office' => [
                "id" => $office->id,
                "name_english" => $validateData['name_english'],
                "name_pashto" => $validateData['name_pashto'],
                "address_farsi" => $validateData['address_farsi'],
                "contact" => $validateData['contact'],
                "email" => $validateData['email'],
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function update(StaffUpdateRequest $request)
    {
        // Validate the request
        $validateData = $request->validated(); // Use validated() for already validated data
        // Begin transaction
        DB::beginTransaction();
        // Find the staff entry by ID
        $staff = Staff::find($validateData['id']);
        if (!$staff) {
            return response()->json([
                'message' => __('app_translation.employee_not_found'),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        $profile = $staff->profile;
        if ($profile !== $request->picture) {
            // Update profile
            $path = storage_path('app/' . $profile);

            if (file_exists($path)) {
                unlink($path);
            }
            // 1.2 update document
            $profile = $this->storeProfile($request, 'staff-profile', 'picture');
            $staff->profile = $profile;
        }

        // Update Staff details
        $staff->contact = $validateData['contact'];
        $staff->email = $validateData['email'];
        $staff->save();

        // Update or create translations
        $languages = ['en' => "english", 'ps' => "pashto", 'fa' => "farsi"];
        foreach ($languages as $code => $name) {
            StaffTran::updateOrCreate(
                [
                    'staff_id' => $staff->id,
                    'language_name' => $code,
                ],
                [
                    'name' => $validateData["name_{$name}"],
                ]
            );
        }
        // Commit transaction
        DB::commit();
        return response()->json([
            'message' => __('app_translation.success'),
            'staff' => [
                "id" => $staff->id,
                "name_english" => $validateData['name_english'],
                "name_pashto" => $validateData['name_pashto'],
                "name_farsi" => $validateData['name_farsi'],
                "contact" => $validateData['contact'],
                "email" => $validateData['email'],
                "picture" => $profile,
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
