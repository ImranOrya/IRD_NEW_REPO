<?php

namespace App\Http\Requests\app\project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class ProjectDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }



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
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        return [
            'abbreviat_english' => 'required|string|min:5',
            'abbreviat_farsi' => 'required|string|min:5',
            'abbreviat_pashto' => 'required|string|min:5',

            'action_plan_english' => 'required|string|min:5',
            'action_plan_farsi' => 'required|string|min:5',
            'action_plan_pashto' => 'required|string|min:5',

            'expected_impact_english' => 'required|string|min:5',
            'expected_impact_farsi' => 'required|string|min:5',
            'expected_impact_pashto' => 'required|string|min:5',

            'expected_outcome_english' => 'required|string|min:5',
            'expected_outcome_farsi' => 'required|string|min:5',
            'expected_outcome_pashto' => 'required|string|min:5',

            'exper_in_health_english' => 'required|string|min:5',
            'exper_in_health_farsi' => 'required|string|min:5',
            'exper_in_health_pashto' => 'required|string|min:5',

            'goals_english' => 'required|string|min:5',
            'goals_farsi' => 'required|string|min:5',
            'goals_pashto' => 'required|string|min:5',

            'id' => 'required|integer|exists:projects,id',

            'main_activities_english' => 'required|string|min:5',
            'main_activities_farsi' => 'required|string|min:5',
            'main_activities_pashto' => 'required|string|min:5',

            'objective_english' => 'required|string|min:5',
            'objective_farsi' => 'required|string|min:5',
            'objective_pashto' => 'required|string|min:5',

            'organization_sen_man_english' => 'required|string|min:5',
            'organization_sen_man_farsi' => 'required|string|min:5',
            'organization_sen_man_pashto' => 'required|string|min:5',

            'preamble_english' => 'required|string|min:5',
            'preamble_farsi' => 'required|string|min:5',
            'preamble_pashto' => 'required|string|min:5',

            'project_intro_english' => 'required|string|min:5',
            'project_intro_farsi' => 'required|string|min:5',
            'project_intro_pashto' => 'required|string|min:5',

            'project_name_english' => 'required|string|min:5',
            'project_name_farsi' => 'required|string|min:5',
            'project_name_pashto' => 'required|string|min:5',
        ];
    }
}
