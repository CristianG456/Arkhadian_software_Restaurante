<?php

namespace App\Domain\Catalog\Http\Requests;

class VariantRequest extends CatalogRequest
{
    protected function prepareForValidation(): void
    {
        $this->prepareMoney('price');
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return ['name' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'], 'price' => $this->moneyRules($creating), 'isDefault' => ['sometimes', 'boolean'], 'isActive' => ['sometimes', 'boolean'], 'isAvailable' => ['sometimes', 'boolean']];
    }
}
