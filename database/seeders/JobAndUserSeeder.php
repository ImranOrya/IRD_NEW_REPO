<?php

namespace Database\Seeders;

use App\Enums\LanguageEnum;
use App\Models\User;
use App\Models\Email;
use App\Enums\RoleEnum;
use App\Models\Contact;
use App\Models\ModelJob;
use App\Models\ModelJobTrans;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JobAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $job = ModelJob::factory()->create([]);
        ModelJobTrans::factory()->create([
            "value" => "Administrator",
            "model_job_id" => $job->id,
            "language_name" => LanguageEnum::default->value,
        ]);
        ModelJobTrans::factory()->create([
            "value" => "مدیر اجرایی",
            "model_job_id" => $job->id,
            "language_name" => LanguageEnum::farsi->value,
        ]);
        ModelJobTrans::factory()->create([
            "value" => "اجرایی مدیر",
            "model_job_id" => $job->id,
            "language_name" => LanguageEnum::pashto->value,
        ]);

        // 
        $job =  ModelJob::factory()->create([]);
        ModelJobTrans::factory()->create([
            "value" => "Manager",
            "model_job_id" => $job->id,
            "language_name" => LanguageEnum::default->value,
        ]);
        ModelJobTrans::factory()->create([
            "value" => "مدیر",
            "model_job_id" => $job->id,
            "language_name" => LanguageEnum::farsi->value,
        ]);
        ModelJobTrans::factory()->create([
            "value" => "مدیر",
            "model_job_id" => $job->id,
            "language_name" => LanguageEnum::pashto->value,
        ]);

        $contact =  Contact::factory()->create([
            "value" => "+93785764809"
        ]);
        $email =  Email::factory()->create([
            "value" => "super@admin.com"
        ]);
        $debuggerEmail =  Email::factory()->create([
            "value" => "debugger@admin.com"
        ]);
        $adminEmail =  Email::factory()->create([
            "value" => "admin@admin.com"
        ]);
        $userEmail =  Email::factory()->create([
            "value" => "user@admin.com"
        ]);

        User::factory()->create([
            "id" => RoleEnum::super->value,
            'full_name' => 'Super User',
            'username' => 'super@admin.com',
            'email_id' =>  $email->id,
            'password' =>  Hash::make("123123123"),
            'status' =>  true,
            'grant_permission' =>  true,
            'role_id' =>  RoleEnum::super,
            'contact_id' =>  $contact->id,
            'job_id' =>  $job->id,
            'destination_id' =>  1,
        ]);
        User::factory()->create([
            "id" => RoleEnum::user->value,
            'full_name' => 'Jalal Bakhti',
            'username' => 'Jalal Bakhti',
            'email_id' =>  $userEmail->id,
            'password' =>  Hash::make("123123123"),
            'status' =>  true,
            'grant_permission' =>  true,
            'role_id' =>  RoleEnum::user,
            'job_id' =>  $job->id,
            'destination_id' =>  16,
        ]);
        User::factory()->create([
            "id" => RoleEnum::debugger->value,
            'full_name' => 'Sayed Naweed Sayedy',
            'username' => 'debugger@admin.com',
            'email_id' =>  $debuggerEmail->id,
            'password' =>  Hash::make("123123123"),
            'status' =>  true,
            'grant_permission' =>  true,
            'role_id' =>  RoleEnum::debugger,
            'job_id' =>  $job->id,
            'destination_id' =>  1,
        ]);
        User::factory()->create([
            "id" => RoleEnum::admin->value,
            'full_name' => 'Waheed Safi',
            'username' => 'Waheed',
            'email_id' =>  $adminEmail->id,
            'password' =>  Hash::make("123123123"),
            'status' =>  true,
            'grant_permission' =>  true,
            'role_id' =>  RoleEnum::admin,
            'job_id' =>  $job->id,
            'destination_id' =>  16,
        ]);
    }
}
