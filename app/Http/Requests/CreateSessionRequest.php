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
            'title.required' => 'Please give your session a name',
            'title.min' => 'Session name must be at least 3 characters long',
            'title.max' => 'Session name cannot exceed 255 characters',
            'description.max' => 'Description cannot exceed 1000 characters',
            'purpose.required' => 'Please select a purpose for this session',
            'purpose.in' => 'Purpose must be either prayer or study',
            'template.required' => 'Please choose a session template',
            'template.in' => 'That template is not available',
            'visibility.required' => 'Please select who can see this session',
            'visibility.in' => 'Visibility must be circle only, open, or anonymous',
            'gender_preference.required' => 'Please select a gender preference',
            'gender_preference.in' => 'Gender preference must be any, male, or female',
            'location_city.max' => 'City name cannot exceed 100 characters',
            'location_country.max' => 'Country name cannot exceed 100 characters',
            'max_members.required' => 'Please specify the maximum number of members',
            'max_members.min' => 'Session must allow at least 2 members',
            'max_members.max' => 'Session cannot have more than 255 members',
            'scheduled_at.required' => 'Please select when you\'d like to start the session',
            'scheduled_at.date_format' => 'Please use the correct date and time format (YYYY-MM-DD HH:MM:SS)',
            'scheduled_at.after' => 'Please schedule the session for a future time',
            'duration_minutes.required' => 'Please specify how long the session will be',
            'duration_minutes.min' => 'Sessions must be at least 10 minutes long',
            'duration_minutes.max' => 'Sessions cannot exceed 8 hours',
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
