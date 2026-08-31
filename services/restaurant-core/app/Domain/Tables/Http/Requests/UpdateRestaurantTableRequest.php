<?php

namespace App\Domain\Tables\Http\Requests;

use App\Support\Text\LabelNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('label'))) {
            $this->merge(['label' => LabelNormalizer::tableDisplay($this->input('label'))]);
        }
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'min:1', 'max:50'],
            'areaId' => ['sometimes', 'required', 'ulid', 'exists:areas,id'],
            'isActive' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Escribe el número o nombre de la mesa.',
            'label.max' => 'El nombre de la mesa no puede superar 50 caracteres.',
            'areaId.exists' => 'El área seleccionada no existe.',
            'isActive.boolean' => 'El estado de la mesa no es válido.',
        ];
    }
}
