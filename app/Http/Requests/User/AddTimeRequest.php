<?php

namespace App\Http\Requests\User;

use App\Enum\PaymentTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddTimeRequest extends FormRequest
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
            'minutes' => ['required', 'numeric'],
            'trip_id' => ['uuid', 'required', Rule::exists('trips', 'id')],
            'charge_from' => ['nullable', Rule::in(PaymentTypeEnum::values())]
        ];
    }
}
