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
            'reported_user_id.required' => 'Please select a user to report',
            'reported_user_id.uuid' => 'The user ID appears to be invalid',
            'reported_user_id.exists' => 'We couldn\'t find that user',
            'reason.required' => 'Please tell us what happened',
            'reason.string' => 'Your reason must be text',
            'reason.min' => 'Please provide at least 10 characters describing the issue',
            'reason.max' => 'Your report cannot exceed 500 characters',
            'stage.required' => 'Please select when this happened',
            'stage.string' => 'Stage must be text',
            'stage.in' => 'Stage must be either during or after the session',
        ];
    }
}
