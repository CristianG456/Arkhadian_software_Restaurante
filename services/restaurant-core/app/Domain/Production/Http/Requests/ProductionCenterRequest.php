<?php

namespace App\Domain\Production\Http\Requests;

use App\Support\Text\LabelNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionCenterRequest extends FormRequest
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
        return ['name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:100'], 'normalizedName' => [$this->isMethod('post') ? 'required' : 'sometimes', Rule::unique('production_centers', 'normalized_name')->ignore($this->route('productionCenter')?->id)], 'isActive' => ['sometimes', 'boolean']];
    }
}
