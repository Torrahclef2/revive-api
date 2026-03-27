<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => 'sometimes|string|max:100',
            'username'          => 'sometimes|string|max:50|alpha_dash|unique:users,username,' . $this->user()->id,
            'headline'          => 'sometimes|nullable|string|max:160',
            'avatar'            => 'sometimes|nullable|string|max:500',
            // Controls who can send this user a direct message
            'messaging_privacy' => 'sometimes|in:everyone,verified_only,disabled',
        ];
    }
}
