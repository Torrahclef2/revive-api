<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSessionRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'purpose' => ['required', 'string', Rule::in(['prayer', 'study'])],
            'template' => ['required', 'string', Rule::in(['intercessory_prayer', 'scripture_study', 'praise_worship', 'open'])],
            'visibility' => ['required', 'string', Rule::in(['circle_only', 'open', 'anonymous'])],
            'gender_preference' => ['required', 'string', Rule::in(['any', 'male', 'female'])],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'max_members' => ['required', 'integer', 'min:2', 'max:255'],
            'scheduled_at' => ['required', 'date_format:Y-m-d H:i:s', 'after:now'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Session title is required',
            'max_members.min' => 'Maximum members must be at least 2',
            'scheduled_at.after' => 'Session must be scheduled for the future',
            'duration_minutes.min' => 'Duration must be at least 10 minutes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set defaults if not provided
        $this->merge([
            'status' => 'upcoming',
            'location_country' => $this->user()?->location_country ?? $this->location_country,
        ]);
    }
}
