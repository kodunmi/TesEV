<?php

namespace App\Http\Requests\V1\General\Notification;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class CreateNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return config('services.controls.allow_app_access');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
            'preview' => ['required', 'string'],
            'markup_body' => ['nullable', 'string'],
            'type' => ['nullable'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = 'There are issues in your input';
        $errors = (new ValidationException($validator))->errors();

        return respondValidationError($message, $errors);
    }
}
