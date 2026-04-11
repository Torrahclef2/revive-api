<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateGroupRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'purpose' => ['required', 'string', Rule::in(['prayer', 'study'])],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please give your group a name',
            'name.string' => 'Group name must be text',
            'name.min' => 'Group name must be at least 3 characters long',
            'name.max' => 'Group name cannot exceed 255 characters',
            'purpose.required' => 'Please select what this group is for',
            'purpose.in' => 'Group purpose must be either prayer or study',
            'description.string' => 'Description must be text',
            'description.max' => 'Description cannot exceed 1000 characters',
        ];
    }
}
