<?php

namespace App\Domain\Tables\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreRestaurantTablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'integer', 'min:1', 'max:9999'],
            'to' => ['required', 'integer', 'gte:from', 'max:9999'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.required' => 'Indica desde qué número crear mesas.',
            'to.required' => 'Indica hasta qué número crear mesas.',
            'to.gte' => 'El número final debe ser igual o mayor al inicial.',
        ];
    }
}
