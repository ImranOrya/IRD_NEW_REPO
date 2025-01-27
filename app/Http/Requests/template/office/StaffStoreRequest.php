<?php

namespace App\Http\Requests\template\office;

use Illuminate\Foundation\Http\FormRequest;

class StaffStoreRequest extends FormRequest
{
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
            'email' => 'required|email',
            'contact' => 'required',
            'name_english' => 'required|string',
            'name_pashto' => 'required|string',
            'name_farsi' => 'required|string',
            'picture' => 'required|mimes:png,jpg,jpeg',
            'staff_type_id' => 'required'
        ];
    }
}
