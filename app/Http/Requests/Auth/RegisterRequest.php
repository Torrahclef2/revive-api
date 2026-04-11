<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'headline' => ['nullable', 'string', 'max:500'],
            'denomination' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email is already registered.',
            'password' => 'Password must be at least 8 characters with uppercase, lowercase, numbers and symbols.',
        ];
    }
}
