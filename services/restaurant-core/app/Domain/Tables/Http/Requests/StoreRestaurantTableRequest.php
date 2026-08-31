<?php

namespace App\Domain\Tables\Http\Requests;

use App\Support\Text\LabelNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('label'))) {
            $label = LabelNormalizer::tableDisplay($this->input('label'));
            $this->merge(['label' => $label]);
        }
    }

    public function rules(): array
    {
        return ['label' => ['required', 'string', 'min:1', 'max:50']];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Escribe el número o nombre de la mesa.',
            'label.max' => 'El nombre de la mesa no puede superar 50 caracteres.',
        ];
    }
}
