<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportUserRequest extends FormRequest
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
            'reported_user_id' => ['required', 'uuid', 'exists:users,id'],
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'stage' => ['required', 'string', Rule::in(['during', 'after'])],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'reported_user_id.required' => 'User ID is required',
            'reported_user_id.uuid' => 'User ID must be a valid UUID',
            'reported_user_id.exists' => 'The reported user does not exist',
            'reason.required' => 'Please provide a reason for the report',
            'reason.min' => 'Reason must be at least 10 characters',
            'stage.required' => 'Stage is required',
            'stage.in' => 'Stage must be either "during" or "after"',
        ];
    }
}
