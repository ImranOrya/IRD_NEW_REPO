<?php

namespace App\Http\Requests\app\news;

use App\Rules\StringOrFileRule;
use Illuminate\Foundation\Http\FormRequest;

class NewsUpdateRequest extends FormRequest
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
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif'];

        return [
            'id' => 'required|integer',
            'visible' => 'required',
            'date' => 'required',
            'visibility_date' => 'nullable|date',
            'cover_pic' => ['required', new StringOrFileRule($allowedExtensions)], // Pass the allowed extensions here
            'title_english' => 'required',
            'title_farsi' => 'required',
            'title_pashto' => 'required',
            'content_english' => 'required',
            'content_farsi' => 'required',
            'content_pashto' => 'required',
            'type' => 'required|integer|exists:news_types,id',
            'priority' => 'required|integer|exists:priorities,id',
        ];
    }
}
