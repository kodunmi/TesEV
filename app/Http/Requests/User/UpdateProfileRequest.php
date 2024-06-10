<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


class UpdateProfileRequest extends FormRequest
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
            'email' => ['nullable', 'email', 'unique:users,email'],
            'phone' => ['nullable', Rule::unique('users', 'phone')],
            'phone_code' => ['required_with:phone'],
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        $message = 'Invalid inputs entered please check';
        $errors = (new ValidationException($validator))->errors();

        return respondValidationError($message, $errors);
    }
}
