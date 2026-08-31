<?php

namespace App\Domain\Areas\Http\Requests;

use App\Support\Text\LabelNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $name = LabelNormalizer::display($this->input('name'));
            $this->merge(['name' => $name, 'normalizedName' => LabelNormalizer::comparable($name)]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:100'],
            'normalizedName' => ['required', 'string', Rule::unique('areas', 'normalized_name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre del área.',
            'name.max' => 'El nombre del área no puede superar 100 caracteres.',
            'normalizedName.unique' => 'Ya existe un área con ese nombre.',
        ];
    }
}
