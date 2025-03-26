<?php

namespace App\Http\Controllers\api\template;

use App\Models\Staff;
use App\Models\Slider;
use App\Enums\StaffEnum;
use App\Models\StaffTran;
use App\Enums\LanguageEnum;
use Illuminate\Http\Request;
use App\Models\OfficeInformation;
use App\Traits\Helper\HelperTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\Controller;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use App\Http\Requests\template\office\StaffStoreRequest;
use App\Http\Requests\template\office\OfficeStoreRequest;
use App\Http\Requests\template\office\StaffUpdateRequest;
use App\Http\Requests\template\office\OfficeUpdateRequest;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;


class AboutController extends Controller
{
    use HelperTrait;
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
    public function publicManager()
    {
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
        return response()->json([
            "manager" => $query,

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
    public function publicDirector()
    {
        $locale = App::getLocale();
        $query = DB::table('staff as s')
            ->where('staff_type_id', StaffEnum::director->value)
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
        return response()->json([
            "director" => $query,

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

    public function publicTechnicalSupports()
    {
        $locale = App::getLocale();
        $query = DB::table('staff as s')
            ->where('staff_type_id', StaffEnum::technical_support->value)
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
            ->get();
        return response()->json([
            "technicalStaff" => $query,

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
    public function publicOffice()
    {
        $locale = App::getLocale();
        $query = OfficeInformation::select()->first();
        if ($query) {
            $address = $query->address_english;
            if ($locale == LanguageEnum::pashto->value) {
                $address = $query->address_pashto;
            } else {
                $address = $query->address_farsi;
            }
            return response()->json([
                "office" => [
                    "id" => $query->id,
                    "address" => $address,
                    "contact" => $query->contact,
                    "email" => $query->email,
                ],

            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
        return response()->json([
            "office" => null,

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

    public function staffDestroy($id)
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
        $document = $this->storePublicDocument($request, "staff-profile", 'picture');
        if (!$document) {
            return response()->json([
                'message' => __('app_translation.failed'),
            ], 400, [], JSON_UNESCAPED_UNICODE);
        }

        // Store Staff data
        $staff = Staff::create([
            'contact' => $validateData['contact'],
            'email' => $validateData['email'],
            'staff_type_id' => $validateData['staff_type_id'],
            'profile' => $document['path'],
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
                "picture" => $document['path'],
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
                "name_english" => $validateData['address_english'],
                "name_pashto" => $validateData['address_pashto'],
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
            $this->deletePublicFile($request->picture);

            // 1.2 update document
            $document = $this->storePublicDocument($request, "staff-profile", 'picture');
            if (!$document) {
                return response()->json([
                    'message' => __('app_translation.failed'),
                    'path' => $document,
                ], 400, [], JSON_UNESCAPED_UNICODE);
            }
            $staff->profile =  $document['path'];
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
                "picture" => $staff->profile,
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function sliderFileUpload(Request $request)
    {
        $receiver = new FileReceiver("file", $request, HandlerFactory::classFromRequest($request));

        if (!$receiver->isUploaded()) {
            throw new UploadMissingFileException();
        }

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $file = $save->getFile();

            $fileName = $this->createChunkUploadFilename($file);
            $fileActualName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $mimetype = $file->getMimeType();
            $authUser = $request->user();

            $directory = $this->getPublicPath("slider/");

            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            $file->move($directory, $fileName);
            // 1. Store in database
            $slider = Slider::create([
                "name" => $fileActualName,
                "path" => 'slider/' . $fileName,
                "extension" => $mimetype,
                "is_active" => true,
                "user_id" => $authUser->id,
                "size" => $fileSize
            ]);
            return response()->json([
                "id" => $slider->id,
                "name" => $slider->name,
                "path" => $slider->path,
                "is_active" => $slider->is_active,
                "saved_by" => $authUser->username,
                "size" => $slider->size,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        // If not finished, send current progress.
        $handler = $save->handler();

        return response()->json([
            "done" => $handler->getPercentageDone(),
            "status" => true,
        ]);
    }

    public function sliders()
    {
        $sliders =  Slider::join('users as u', 'u.id', 'sliders.user_id')
            ->select(
                'sliders.id',
                'sliders.name',
                'sliders.path',
                'sliders.is_active',
                'u.username as saved_by',
                'sliders.size'
            )
            ->orderBy("sliders.id", "desc")
            ->get();
        return response()->json($sliders, 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function publicSliders()
    {
        $sliders =  Slider::where('is_active', true)
            ->join('users as u', 'u.id', 'sliders.user_id')
            ->select(
                'sliders.id',
                'sliders.name',
                'sliders.path',
                'sliders.is_active',
                'u.username as saved_by',
                'sliders.size'
            )
            ->orderBy("sliders.id", "desc")
            ->get();
        return response()->json($sliders, 200, [], JSON_UNESCAPED_UNICODE);
    }
    public function changeStatusSlider(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:sliders,id', // Ensure the slider exists
            'is_active' => 'required|in:1,0'
        ]);

        $slider = Slider::find($request->slider_id);
        $slider->is_active = $request->is_active;
        $slider->save();

        return response()->json([
            'message' => __('app_translation.update'),
            'slider' => $slider
        ]);
    }
    public function sliderDestroy($id)
    {
        $slider = Slider::find($id);
        if ($slider) {
            // Begin transaction
            DB::beginTransaction();
            // Delete documents
            $path = storage_path('app/' . $slider->path);

            if (file_exists($path)) {
                unlink($path);
            }
            $slider->delete();

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
}
