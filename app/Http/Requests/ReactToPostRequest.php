<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReactToPostRequest extends FormRequest
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
            'reaction' => ['required', 'string', Rule::in(['amen', 'heart', 'pray'])],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'reaction.required' => 'Please select a reaction',
            'reaction.string' => 'Reaction must be text',
            'reaction.in' => 'That reaction is not available. Please choose from amen, heart, or pray',
        ];
    }
}
