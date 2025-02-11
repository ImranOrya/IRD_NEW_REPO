<?php

namespace App\Http\Requests\app\ngo;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;

class NgoUpdatedMoreInformationRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * This method is called before the validation rules are applied.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Get the raw contents field from the request (this is your JSON string)
        $jsonData = $this->input('content');

        // Decode the JSON string into an array
        if ($jsonData) {
            $decodedData = json_decode($jsonData, true);

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
     * Determine if the user is authorized to make this request.
     */
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
            'id' => 'required',
            'vision_english' => 'required|string',
            'vision_farsi' => 'required|string',
            'vision_pashto' => 'required|string',
            'mission_english' => 'required|string',
            'mission_farsi' => 'required|string',
            'mission_pashto' => 'required|string',
            'general_objes_english' => 'required|string',
            'general_objes_farsi' => 'required|string',
            'general_objes_pashto' => 'required|string',
            'objes_in_afg_english' => 'required|string',
            'objes_in_afg_farsi' => 'required|string',
            'objes_in_afg_pashto' => 'required|string',
        ];
    }
}
