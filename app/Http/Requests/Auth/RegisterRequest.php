<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(6)],
            'phone' => ['required', Rule::unique('users', 'phone')],
            'phone_code' => ['required_with:phone'],
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'fcm_token' => ['required', 'string']
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = 'Invalid inputs entered please check';
        $errors = (new ValidationException($validator))->errors();

        return respondValidationError($message, $errors);
    }
}
