<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Enums\StaffEnum;
use Illuminate\Database\Seeder;
use App\Models\OfficeInformation;
use App\Models\StaffTran;

class AboutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->officeInformation();
        $this->manager();
        $this->director();
        $this->technicalSupport();
    }
    public function officeInformation()
    {
        OfficeInformation::create([
            "address_english" => "Sehat-e-Ama Square, Wazir Akbar khan Road, Kabul, Afghanistan",
            "address_farsi" => "چهار راهی صحت عامه، جاده وزیرمحمد اکبر خان، کابل، افغانستان",
            "address_pashto" => "د عامې روغتيا څلور لارې، د وزیرمحمد اکبر خان سرک ، کابل، افغانستان",
            "contact" => "+93202301374",
            "email" => "info.access@moph.gov.af",
        ]);
    }
    public function manager()
    {
        $staff = Staff::create([
            "staff_type_id" => StaffEnum::manager,
            "contact" => "+93202301375",
            "email" => "manager@moph.gov.af",
            "profile" => 'placeholders/user-image.jpg',
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "en",
            'name' => "Manager",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "fa",
            'name' => "مدیر",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "ps",
            'name' => "مدیر",
        ]);
    }
    public function director()
    {
        $staff = Staff::create([
            "staff_type_id" => StaffEnum::director,
            "contact" => "+93202301376",
            "email" => "director@moph.gov.af",
            "profile" => 'placeholders/user-image.jpg',
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "en",
            'name' => "Director",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "fa",
            'name' => "رئیس",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "ps",
            'name' => "رئیس",
        ]);
    }
    public function technicalSupport()
    {
        // 1.
        $staff = Staff::create([
            "staff_type_id" => StaffEnum::technical_support,
            "contact" => "+93785764809",
            "email" => "sayednaweedsayedy@gmail.com",
            "profile" => 'placeholders/sayed_naweed.jpg',
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "en",
            'name' => "Sayed Naweed (Software Developer)",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "fa",
            'name' => "سید نوید (توسعه دهنده نرم افزار)",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "ps",
            'name' => "سید نوید (د سافټویر جوړونکی)",
        ]);
        // 2. 
        $staff = Staff::create([
            "staff_type_id" => StaffEnum::technical_support,
            "contact" => "+93767028775",
            "email" => "waheedsafi@gmail.com",
            "profile" => 'placeholders/waheed.jpg',
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "en",
            'name' => "Waheed Safi (Software Developer)",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "fa",
            'name' => "وحید صافی (توسعه دهنده نرم افزار)",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "ps",
            'name' =>  "وحید صافی (د سافټویر جوړونکی)",
        ]);
        // 3. 
        $staff = Staff::create([
            "staff_type_id" => StaffEnum::technical_support,
            "contact" => "+93773757829",
            "email" => "jalalbakhti@gmail.com",
            "profile" => 'placeholders/jalal.jpg',
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "en",
            'name' => "Jalal Bakhti (Software Developer)",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "fa",
            'name' => "جلال بختی (توسعه دهنده نرم افزار)",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "ps",
            'name' =>  "جلال بختی (د سافټویر جوړونکی)",
        ]);
        // 4. 
        $staff = Staff::create([
            "staff_type_id" => StaffEnum::technical_support,
            "contact" => "+93773527252",
            "email" => "imranoraya@gmail.com",
            "profile" => 'placeholders/imran.jpg',
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "en",
            'name' => "Imran Orya (Software Developer)",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "fa",
            'name' => "عمران اوریا (توسعه‌دهنده نرم‌افزار)",
        ]);
        StaffTran::create([
            'staff_id' => $staff->id,
            'language_name' => "ps",
            'name' =>  "عمران اوریا (د سافټویر جوړونکی)",
        ]);
    }
}
