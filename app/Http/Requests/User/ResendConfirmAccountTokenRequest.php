<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class ResendConfirmAccountTokenRequest extends FormRequest
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
            "token_id" => ['required', 'exists:tokens,id', 'uuid']
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = 'Invalid input entered';
        $errors = (new ValidationException($validator))->errors();

        return respondValidationError($message, $errors);
    }
}
