<?php

namespace Database\Seeders;

use App\Models\Gender;
use App\Models\NgoType;
use App\Models\NidType;
use App\Models\Setting;
use App\Enums\StaffEnum;
use App\Models\Language;
use App\Models\NewsType;
use App\Models\Priority;
use App\Models\TimeUnit;
use App\Models\Translate;
use App\Enums\SettingEnum;
use App\Models\StatusType;
use App\Enums\PriorityEnum;
use App\Enums\TimeUnitEnum;
use App\Enums\Type\NgoTypeEnum;
use App\Models\RequestType;
use App\Models\NgoTypeTrans;
use App\Models\NidTypeTrans;
use App\Models\NewsTypeTrans;
use App\Models\PriorityTrans;
use App\Models\SettingTimeUnit;
use Illuminate\Database\Seeder;
use App\Enums\Type\StatusTypeEnum;
use Illuminate\Support\Facades\DB;
use Database\Seeders\CheckListSeeder;

/*
1. If you add new Role steps are:
    1. Add to following:
        - RoleEnum
        - RoleSeeder
        - RolePermissionSeeder (Define which permissions role can access)
        - Optional: Set Role on User go to JobAndUserSeeder Then UserPermissionSeeder


2. If you add new Permission steps are:
    1. Add to following:
        - PermissionEnum
        - SubPermissionEnum (In case has Sub Permissions)
        - PermissionSeeder
        - SubPermissionSeeder Then SubPermissionEnum (I has any sub permissions) 
        - RolePermissionSeeder (Define Which Role can access the permission)
        - Optional: Set Permission on User go to JobAndUserSeeder Then UserPermissionSeeder

        
3. If you add new Sub Permission steps are:
    1. Add to following:
        - SubPermissionEnum
        - SubPermissionSeeder
        - RolePermissionSeeder (Define Which Role can access the permission)
        - Optional: Set Permission on User go to JobAndUserSeeder Then UserPermissionSeeder
*/

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->languages();
        $this->gender();
        $this->call(CountrySeeder::class);
        $this->call(DestinationSeederSecond::class);
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(SubPermissionSeeder::class);
        $this->call(RolePermissionSeeder::class);
        $this->call(JobAndUserSeeder::class);
        $this->call(CheckListSeeder::class);
        $this->call(UserPermissionSeeder::class);
        $this->call(NotifierSeeder::class);

        $this->ngoTypes();
        $this->statusType();
        $this->settings();
        $this->newsTypes();
        $this->priorityTypes();
        $this->nidTypes();
        $this->staffTypes();
        $this->call(AboutSeeder::class);
        $this->call(ApprovalSeeder::class);
    }
    public function ngoTypes()
    {
        $international = NgoType::factory()->create([
            'id' => NgoTypeEnum::International,
        ]);
        NgoTypeTrans::factory()->create([
            "value" => "بین المللی",
            "language_name" => "fa",
            "ngo_type_id" => $international->id
        ]);
        NgoTypeTrans::factory()->create([
            "value" => "نړیوال",
            "language_name" => "ps",
            "ngo_type_id" => $international->id
        ]);
        NgoTypeTrans::factory()->create([
            "value" => "International",
            "language_name" => "en",
            "ngo_type_id" => $international->id
        ]);

        $intergovernmental = NgoType::factory()->create([
            'id' => NgoTypeEnum::Intergovernmental,

        ]);
        NgoTypeTrans::factory()->create([
            "value" => "بین الدولتی",
            "language_name" => "fa",
            "ngo_type_id" => $intergovernmental->id
        ]);

        NgoTypeTrans::factory()->create([
            "value" => "بین الدولتی",
            "language_name" => "ps",
            "ngo_type_id" => $intergovernmental->id
        ]);
        NgoTypeTrans::factory()->create([
            "value" => "Intergovernmental",
            "language_name" => "en",
            "ngo_type_id" => $intergovernmental->id
        ]);

        $domestic = NgoType::factory()->create([
            'id' => NgoTypeEnum::Domestic,

        ]);
        NgoTypeTrans::factory()->create([
            "value" => "داخلی",
            "language_name" => "fa",
            "ngo_type_id" => $domestic->id
        ]);
        NgoTypeTrans::factory()->create([
            "value" => "کورني",
            "language_name" => "ps",
            "ngo_type_id" => $domestic->id
        ]);
        NgoTypeTrans::factory()->create([
            "value" => "Domestic",
            "language_name" => "en",
            "ngo_type_id" => $domestic->id
        ]);
    }
    public function newsTypes()
    {
        $newsType = NewsType::create([]);
        NewsTypeTrans::create([
            "value" => "اخبار صحی",
            "language_name" => "fa",
            "news_type_id" => $newsType->id
        ]);
        NewsTypeTrans::create([
            "value" => "روغتیا خبرونه",
            "language_name" => "ps",
            "news_type_id" => $newsType->id
        ]);
        NewsTypeTrans::create([
            "value" => "Health News",
            "language_name" => "en",
            "news_type_id" => $newsType->id
        ]);

        $newsType = NewsType::create([]);
        NewsTypeTrans::create([
            "value" => "اخبار جهان",
            "language_name" => "fa",
            "news_type_id" => $newsType->id
        ]);
        NewsTypeTrans::create([
            "value" => "نړیوال خبرونه",
            "language_name" => "ps",
            "news_type_id" => $newsType->id
        ]);
        NewsTypeTrans::create([
            "value" => "International News",
            "language_name" => "en",
            "news_type_id" => $newsType->id
        ]);
    }
    public function priorityTypes()
    {
        $priority = Priority::create([
            'id' => PriorityEnum::high->value
        ]);
        PriorityTrans::create([
            "value" => "اولویت بالا",
            "language_name" => "fa",
            "priority_id" => $priority->id
        ]);
        PriorityTrans::create([
            "value" => "لوړ لومړیتوب",
            "language_name" => "ps",
            "priority_id" => $priority->id
        ]);
        PriorityTrans::create([
            "value" => "High Priority",
            "language_name" => "en",
            "priority_id" => $priority->id
        ]);
        $priority = Priority::create([
            'id' => PriorityEnum::medium->value
        ]);
        PriorityTrans::create([
            "value" => "اولویت متوسط",
            "language_name" => "fa",
            "priority_id" => $priority->id
        ]);
        PriorityTrans::create([
            "value" => "منځنی لومړیتوب",
            "language_name" => "ps",
            "priority_id" => $priority->id
        ]);
        PriorityTrans::create([
            "value" => "Medium Priority",
            "language_name" => "en",
            "priority_id" => $priority->id
        ]);
        $priority = Priority::create([
            'id' => PriorityEnum::low->value
        ]);
        PriorityTrans::create([
            "value" => "اولویت پایین",
            "language_name" => "fa",
            "priority_id" => $priority->id
        ]);
        PriorityTrans::create([
            "value" => "ټیټ لومړیتوب",
            "language_name" => "ps",
            "priority_id" => $priority->id
        ]);
        PriorityTrans::create([
            "value" => "Low Priority",
            "language_name" => "en",
            "priority_id" => $priority->id
        ]);
    }
    public function nidTypes()
    {
        $nid = NidType::create([]);
        NidTypeTrans::create([
            "value" => "پاسپورت",
            "language_name" => "fa",
            "nid_type_id" => $nid->id
        ]);
        NidTypeTrans::create([
            "value" => "پاسپورټ",
            "language_name" => "ps",
            "nid_type_id" => $nid->id
        ]);
        NidTypeTrans::create([
            "value" => "Passport",
            "language_name" => "en",
            "nid_type_id" => $nid->id
        ]);
        $nid = NidType::create([]);
        NidTypeTrans::create([
            "value" => "تذکره",
            "language_name" => "fa",
            "nid_type_id" => $nid->id
        ]);
        NidTypeTrans::create([
            "value" => "تذکره",
            "language_name" => "ps",
            "nid_type_id" => $nid->id
        ]);
        NidTypeTrans::create([
            "value" => "ID card",
            "language_name" => "en",
            "nid_type_id" => $nid->id
        ]);
    }
    public function statusType()
    {
        $statustype =  StatusType::factory()->create([
            'id' => StatusTypeEnum::register_form_not_completed,
        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'en',
            'name' => 'Register form not completed'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'fa',
            'name' => 'فرم ثبت نام تکمیل نشده است'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'ps',
            'name' => 'د نوم لیکنې فورمه نه ده بشپړه شوې'

        ]);

        $statustype =  StatusType::factory()->create([
            'id' => StatusTypeEnum::register_form_completed,
        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'en',
            'name' => 'Register Form completed'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'fa',
            'name' => 'فرم ثبت نام تکمیل شد'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'ps',
            'name' => 'د نوم لیکنې فورمه بشپړه شوه'

        ]);

        $statustype =  StatusType::factory()->create([
            'id' => StatusTypeEnum::signed_register_form_submitted,
        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'en',
            'name' => 'Signed register form submitted'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'fa',
            'name' => 'فرم ثبت نام امضا شده ارسال شد'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'ps',
            'name' => 'لاسلیک شوی د نوم لیکنې فورمه وسپارل شوه'

        ]);

        $statustype = StatusType::factory()->create([
            'id' => StatusTypeEnum::registered,
        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'en',
            'name' => 'Registered'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'fa',
            'name' => 'ثبت شده'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'ps',
            'name' => 'ثبت شوی'

        ]);

        $statustype = StatusType::factory()->create([
            'id' => StatusTypeEnum::blocked,
        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'en',
            'name' => 'Blocked'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'fa',
            'name' => 'مسدود'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'ps',
            'name' => 'بند'

        ]);

        $statustype = StatusType::factory()->create([
            'id' => StatusTypeEnum::registration_expired,
        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'en',
            'name' => 'Registration expired'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'fa',
            'name' => 'ثبت نام به پایان رسیده'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'ps',
            'name' => 'نوم لیکنه پای ته ورسېده.'

        ]);

        $statustype = StatusType::factory()->create([
            'id' => StatusTypeEnum::registration_extended,
        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'en',
            'name' => 'Registration extended'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'fa',
            'name' => 'ثبت نام تمدید شد'

        ]);
        DB::table('status_type_trans')->insert([
            'status_type_id' => $statustype->id,
            'language_name' => 'ps',
            'name' => 'د نوم لیکنې موده غځول شوې ده'

        ]);
    }
    public function staffTypes()
    {
        DB::table('staff_types')->insert([
            'id' => StaffEnum::manager,
            'name' => "manager",
        ]);
        DB::table('staff_types')->insert([
            'id' => StaffEnum::director,
            'name' => "director",
        ]);
        DB::table('staff_types')->insert([
            'id' => StaffEnum::technical_support,
            'name' => "technical_support",
        ]);
    }
    public function settings()
    {
        $unit = TimeUnit::factory()->create([
            "id" => TimeUnitEnum::day->value,
            "name" => "Day",
        ]);
        $this->Translate("روز", "fa", $unit->id, TimeUnit::class);
        $this->Translate("ورځ", "ps", $unit->id, TimeUnit::class);

        $setting = Setting::factory()->create([
            "id" => SettingEnum::registeration_expire_time->value,
            "name" => "Register Expiration Deadline",
            "value" => "365", // days
        ]);

        SettingTimeUnit::factory()->create([
            "time_unit_id" => TimeUnitEnum::day->value,
            "setting_id" => $setting->id,
        ]);
    }
    protected function gender()
    {

        Gender::create([
            'name_en' => 'Male',
            'name_fa' => 'مرد',
            'name_ps' => 'نارینه'
        ]);

        Gender::create([
            'name_en' => 'Famale',
            'name_fa' => 'زن',
            'name_ps' => 'ښځینه'
        ]);
    }
    public function languages(): void
    {
        Language::factory()->create([
            "name" => "en"
        ]);
        Language::factory()->create([
            "name" => "ps"
        ]);
        Language::factory()->create([
            "name" => "fa"
        ]);
    }
    public function Translate($value, $language, $translable_id, $translable_type): void
    {
        Translate::factory()->create([
            "value" => $value,
            "language_name" => $language,
            "translable_type" => $translable_type,
            "translable_id" => $translable_id,
        ]);
    }
}
