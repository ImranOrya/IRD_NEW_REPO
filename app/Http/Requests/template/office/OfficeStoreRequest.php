<?php

namespace App\Http\Requests\template\office;

use Illuminate\Foundation\Http\FormRequest;

class OfficeStoreRequest extends FormRequest
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
            'email' => 'required|email|unique:office_information,email',
            'contact' => 'required|unique:office_information,contact',
            'address_english' => 'required|string',
            'address_farsi' => 'required|string',
            'address_pashto' => 'required|string',
        ];
    }
}
