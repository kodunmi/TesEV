<?php

namespace App\Http\Requests\Core;

use Illuminate\Foundation\Http\FormRequest;

class AddCardRequest extends FormRequest
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
            'exp_month' => ['required', 'digits:2'],
            'exp_year' => ['required', 'digits:4'],
            'number' => ['required', 'integer'],
        ];
    }
}
