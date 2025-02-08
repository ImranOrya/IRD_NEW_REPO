<?php

namespace App\Http\Requests\app\ngo\director;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;

class StoreDirectorRequest extends FormRequest
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
        $jsonData = $this->input('contents');

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
            'name_english' => 'required|string|max:128',
            'name_pashto' => 'required|string|max:128',
            'name_farsi' => 'required|string|max:128',
            'gender.id' => 'required|exists:genders,id',
            'email' => 'required|email|unique:emails,value',
            'contact' => 'required|unique:contacts,value',
            'nid' => 'required|string|max:50',
            'identity_type.id' => 'required|exists:nid_types,id',
            'nationality.id' => 'required|exists:countries,id',
            'province.id' => 'required|exists:provinces,id',
            'district.id' => 'required|exists:districts,id',
            'area_english' => 'required|string|max:255',
            'area_pashto' => 'required|string|max:255',
            'area_farsi' => 'required|string|max:255',
            'surname_english' => 'required|string|max:255',
            'surname_pashto' => 'required|string|max:255',
            'surname_farsi' => 'required|string|max:255',
            'is_active' => 'required',
        ];
    }
}
