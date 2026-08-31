<?php

namespace App\Domain\Areas\Http\Requests;

use App\Domain\Areas\Models\Area;
use App\Support\Text\LabelNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
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
        $area = $this->route('area');
        $areaId = $area instanceof Area ? $area->id : $area;

        return [
            'name' => ['sometimes', 'required', 'string', 'min:1', 'max:100'],
            'normalizedName' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('areas', 'normalized_name')->ignore($areaId),
            ],
            'isActive' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre del área.',
            'name.max' => 'El nombre del área no puede superar 100 caracteres.',
            'normalizedName.unique' => 'Ya existe un área con ese nombre.',
            'isActive.boolean' => 'El estado del área no es válido.',
        ];
    }
}
