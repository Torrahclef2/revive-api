<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'             => 'required|in:prayer,bible_study',
            'description'      => 'nullable|string|max:1000',
            // Between 2 and 10 participants as per domain requirements
            'max_participants' => 'required|integer|min:2|max:10',
            // Duration in minutes: minimum 10, maximum 120
            'duration'         => 'required|integer|min:10|max:120',
            'privacy'          => 'required|in:public,anonymous,group',
            // Optional future start time. Must be at least 5 minutes from now.
            'scheduled_at'     => 'nullable|date|after:' . now()->addMinutes(5)->toISOString(),
            // Optional meta array: e.g. [{ key: "prayer_request", value: "..." }]
            'meta'             => 'nullable|array',
            'meta.*.key'       => 'required_with:meta|string|max:100',
            'meta.*.value'     => 'required_with:meta|string',
        ];
    }
}
