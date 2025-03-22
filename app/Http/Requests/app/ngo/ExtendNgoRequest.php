<?php

namespace App\Http\Requests\app\ngo;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;

class ExtendNgoRequest extends FormRequest
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
            "name_english" => 'required|string|max:128|min:5',
            "name_farsi" =>  'required|string|max:128|min:5',
            "name_pashto" => 'required|string|max:128|min:5',
            "abbr" => "required|string",
            "contact" => "required",
            "email" => "required",
            "moe_registration_no" => "required|unique:ngos,moe_registration_no",
            "province.id" => "required|exists:provinces,id",
            "district.id" => "required|exists:districts,id",
            "area_english" => "required|max:128|min:5",
            "area_pashto" => "required|max:128|min:5",
            "area_farsi" => "required|max:128|min:5",
            // representer
            "new_represent" => "required|boolean",
            "repre_name_english" => "required_if:new_represent,true|max:128|min:3",
            "repre_name_farsi" => "required_if:new_represent,true|max:128|min:3",
            "repre_name_pashto" => "required_if:new_represent,true|max:128|min:3",
            "prev_rep.id" => "required_if:new_represent,false",
            // director
            "new_director" => "required|boolean",
            "prev_dire.id" => "required_if:new_director,false",
            "director_name_english" => "required_if:new_director,true|max:128|min:3",
            "director_name_farsi"  => "required_if:new_director,true|max:128|min:3",
            "director_name_pashto" => "required_if:new_director,true|max:128|min:3",
            "surname_english" => "required_if:new_director,true|max:128|min:3",
            "surname_pashto" => "required_if:new_director,true|max:128|min:3",
            "surname_farsi" => "required_if:new_director,true|max:128|min:3",
            "director_contact" => "required_if:new_director,true",
            "director_email" => "required_if:new_director,true",
            "gender.id" => "required_if:new_director,true:new_director,true|exists:genders,id",
            "nationality.id" => "required_if:new_director,true:new_director,true|exists:countries,id",
            "identity_type.id" => "required_if:new_director,true|exists:nid_types,id",
            "nid" => "required_if:new_director,true|unique:directors,nid_no",
            "director_province.id" => "required_if:new_director,true|exists:provinces,id",
            "director_dis.id" => "required_if:new_director,true|exists:districts,id",
            "director_area_english" => "required_if:new_director,true|max:128|min:5",
            "director_area_farsi" => "required_if:new_director,true|max:128|min:5",
            "director_area_pashto" => "required_if:new_director,true|max:128|min:5",
            // ngo details
            "vision_english" =>  "required|min:5",
            "vision_pashto" =>  "required|min:5",
            "vision_farsi" =>  "required|min:5",
            "mission_english" =>  "required|min:5",
            "mission_pashto" =>  "required|min:5",
            "mission_farsi" =>  "required|min:5",
            "general_objes_english" =>  "required|min:10",
            "general_objes_pashto" =>  "required|min:10",
            "general_objes_farsi" =>  "required|min:10",
            "objes_in_afg_english" =>  "required|min:10",
            "objes_in_afg_pashto" =>  "required|min:10",
            "objes_in_afg_farsi" =>  "required|min:10",
        ];
    }
}
