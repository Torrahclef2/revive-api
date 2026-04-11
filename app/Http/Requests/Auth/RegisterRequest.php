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
            'name.required' => 'Please enter your name',
            'name.string' => 'Name must be text',
            'name.max' => 'Name cannot exceed 255 characters',
            'username.required' => 'Please choose a username',
            'username.string' => 'Username must be text',
            'username.min' => 'Username must be at least 3 characters',
            'username.max' => 'Username cannot exceed 255 characters',
            'username.unique' => 'Sorry, that username is already taken. Try another!',
            'email.required' => 'Please enter your email address',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'That email is already registered. Try logging in instead!',
            'password.required' => 'Please enter a password',
            'password.min' => 'Password must be at least 8 characters',
            'password.mixed' => 'Password must include uppercase and lowercase letters',
            'password.numbers' => 'Password must include at least one number',
            'password.symbols' => 'Password must include at least one special character (!@#$%...)',
            'gender.required' => 'Please select your gender',
            'gender.string' => 'Gender must be text',
            'gender.in' => 'Gender must be male, female, or other',
            'headline.string' => 'Headline must be text',
            'headline.max' => 'Headline cannot exceed 500 characters',
            'denomination.string' => 'Denomination must be text',
            'denomination.max' => 'Denomination cannot exceed 255 characters',
        ];
    }
}
