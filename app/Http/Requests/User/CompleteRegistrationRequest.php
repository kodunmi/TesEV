<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;


class CompleteRegistrationRequest extends FormRequest
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
            'license_state' => ['required', 'string'],
            'poster_code' => ['required', 'string'],
            'license_number' => ['required', 'string'],
            'expiration_date' => ['required', 'date'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = 'Invalid input entered';
        $errors = (new ValidationException($validator))->errors();

        return respondValidationError($message, $errors);
    }
}
