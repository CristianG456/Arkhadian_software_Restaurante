<?php

namespace App\Domain\Tables\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'areaId' => ['required', 'ulid', 'exists:areas,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'areaId.required' => 'Selecciona el área de destino.',
            'areaId.exists' => 'El área de destino no existe.',
        ];
    }
}
