<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GetCostingRequest extends FormRequest
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
            'vehicle_id' => ['uuid', 'required', Rule::exists('vehicles', 'id')],
            'start_time' => ['required', 'date_format:"Y-m-d H:i:s'],
            'end_time' => ['required', 'date_format:"Y-m-d H:i:s'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = 'Invalid data provided';
        $errors = (new ValidationException($validator))->errors();

        return respondValidationError($message, $errors);
    }
}
