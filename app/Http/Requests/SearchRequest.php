<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'type' => ['required', 'string', Rule::in(['users', 'groups', 'sessions'])],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Please enter a search term',
            'q.min' => 'Your search term must be at least 2 characters long',
            'q.max' => 'Your search term cannot exceed 255 characters',
            'type.required' => 'Please specify what you\'d like to search for',
            'type.in' => 'You can search for users, groups, or sessions',
        ];
    }
}
