<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class UploadComplianceRequest extends FormRequest
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
            'driver_license_front' => ['required', File::image()->max('1mb')],
            'driver_license_back' => ['required', File::image()->max('1mb')],
            'photo' => ['required', File::image()->max('1mb')],
            'license_state' => ['required', 'string'],
            'poster_code' => ['required', 'string'],
            'license_number' => ['required', 'string'],
            'expiration_date' => ['required', 'date'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = 'Invalid input';
        $errors = (new ValidationException($validator))->errors();

        return respondValidationError($message, $errors);
    }
}
