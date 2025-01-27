<?php

namespace App\Http\Requests\template\office;

use Illuminate\Foundation\Http\FormRequest;

class OfficeUpdateRequest extends FormRequest
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
            'id' => 'required',
            'email' => 'required|email',
            'contact' => 'required',
            'address_english' => 'required|string',
            'address_farsi' => 'required|string',
            'address_pashto' => 'required|string',
        ];
    }
}
