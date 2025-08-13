<?php

namespace App\Http\Requests\app\project;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;

class ProjectStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */


    protected function prepareForValidation()
    {
        // Get the raw contents field from the request (this is your JSON string)
        $jsonData = $this->input('content');

        // Decode the JSON string into an array
        if ($jsonData) {
            $decodedData = json_decode($jsonData, true);

            // Log::info($decodedData);

            // If the JSON is valid, merge the decoded data into the request
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge($decodedData);
            } else {
                // Log or handle error if JSON is invalid
                Log::error('Invalid JSON data received', ['data' => $jsonData]);
            }
        }
    }
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "project_name_english" => 'required|string|max:128|min:5',
            "project_name_farsi" =>  'required|string|max:128|min:5',
            "project_name_pashto" => 'required|string|max:128|min:5',
            // Dates
            'start_date' => 'required|before:end_date',
            'end_date'   => 'required|after:start_date',

            'budget' => 'required|numeric|min:0',

            // Donor
            'donor.id'       => 'required|numeric|exists:donors,id',
            'donor_register_no' => 'required|string|max:32',


            // Currency
            'currency.id'       => 'required|numeric|exists:currencies,id',

            'centers_list'                => 'required|array|min:1',
            'centers_list.*.province.id'  => 'required|numeric|exists:provinces,id',
            'centers_list.*.district.*.id'   => 'required|numeric|exists:districts,id',

            'centers_list.*.villages'                 => 'required|array',
            'centers_list.*.villages.*.village_english'   => 'required|string',
            'centers_list.*.villages.*.village_farsi'     => 'required|string',
            'centers_list.*.villages.*.village_pashto'    => 'required|string',

            'centers_list.*.health_centers_english' => 'required|string',
            'centers_list.*.health_centers_farsi'   => 'required|string',
            'centers_list.*.health_centers_pashto'  => 'required|string',

            'centers_list.*.budget'                => 'required|numeric|min:0',
            'centers_list.*.direct_benefi'         => 'required|numeric|min:0',
            'centers_list.*.in_direct_benefi'      => 'required|numeric|min:0',

            'centers_list.*.address_english' => 'required|string',
            'centers_list.*.address_farsi'   => 'required|string',
            'centers_list.*.address_pashto'  => 'required|string',

            'centers_list.*.health_worker_english' => 'required|string',
            'centers_list.*.health_worker_farsi'   => 'required|string',
            'centers_list.*.health_worker_pashto'  => 'required|string',

            'centers_list.*.fin_admin_employees_english' => 'required|string',
            'centers_list.*.fin_admin_employees_farsi'   => 'required|string',
            'centers_list.*.fin_admin_employees_pashto'  => 'required|string',


            // project manager

            // 'pro_manager_name_english' => 'required|string',
            // 'pro_manager_name_farsi'   => 'required|string',
            // 'pro_manager_name_pashto'  => 'required|string',
            // 'pro_manager_contact'      => 'required|string|max:20',
            // 'pro_manager_email'        => 'required|email',


            // project details
            "preamble_pashto" =>  "required|min:5",
            "preamble_farsi" =>  "required|min:5",
            "preamble_english" =>  "required|min:5",

            "abbreviat_english" =>  "required|min:5",
            "abbreviat_pashto" =>  "required|min:5",
            "abbreviat_farsi" =>  "required|min:5",

            "exper_in_health_english" =>  "required|min:5",
            "exper_in_health_farsi" =>  "required|min:5",
            "exper_in_health_pashto" =>  "required|min:5",

            "project_intro_english" =>  "required|min:5",
            "project_intro_pashto" =>  "required|min:5",
            "project_intro_farsi" =>  "required|min:5",

            "goals_english" =>  "required|min:5",
            "goals_pashto" =>  "required|min:5",
            "goals_farsi" =>  "required|min:5",

            "objective_english" =>  "required|min:5",
            "objective_farsi" =>  "required|min:5",
            "objective_pashto" =>  "required|min:5",

            "expected_outcome_english" =>  "required|min:5",
            "expected_outcome_farsi" =>  "required|min:5",
            "expected_outcome_pashto" =>  "required|min:5",

            "expected_impact_english" =>  "required|min:5",
            "expected_impact_farsi" =>  "required|min:5",
            "expected_impact_pashto" =>  "required|min:5",

            "main_activities_english" =>  "required|min:5",
            "main_activities_farsi" =>  "required|min:5",
            "main_activities_pashto" =>  "required|min:5",


            "action_plan_english" =>  "required|min:5",
            "action_plan_pashto" =>  "required|min:5",
            "action_plan_farsi" =>  "required|min:5",

            "organization_sen_man_english" =>  "required|min:5",
            "organization_sen_man_farsi" =>  "required|min:5",
            "organization_sen_man_pashto" =>  "required|min:5",



        ];
    }
}
