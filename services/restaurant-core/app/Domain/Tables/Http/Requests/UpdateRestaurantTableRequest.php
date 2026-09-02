<?php

namespace App\Domain\Tables\Http\Requests;

use App\Domain\Tables\Models\RestaurantTable;
use App\Support\Text\LabelNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('label'))) {
            $label = LabelNormalizer::tableDisplay($this->input('label'));
            $this->merge([
                'label' => $label,
                'normalizedLabel' => LabelNormalizer::comparable($label),
            ]);
        }
    }

    public function rules(): array
    {
        $table = $this->route('table');
        $tableId = $table instanceof RestaurantTable ? $table->id : $table;
        $areaId = $table instanceof RestaurantTable ? $table->area_id : null;

        return [
            'label' => ['sometimes', 'required', 'string', 'min:1', 'max:50'],
            'normalizedLabel' => [
                'sometimes',
                'required',
                'string',
                Rule::unique('restaurant_tables', 'normalized_label')
                    ->where('area_id', $areaId)
                    ->ignore($tableId),
            ],
            'isActive' => ['sometimes', 'required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'label.required' => 'Escribe el número o nombre de la mesa.',
            'label.max' => 'El nombre de la mesa no puede superar 50 caracteres.',
            'normalizedLabel.unique' => 'Ya existe una mesa con ese número o nombre en el área.',
            'isActive.boolean' => 'El estado de la mesa no es válido.',
        ];
    }
}
