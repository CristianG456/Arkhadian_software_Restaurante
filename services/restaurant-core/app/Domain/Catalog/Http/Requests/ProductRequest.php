<?php

namespace App\Domain\Catalog\Http\Requests;

class ProductRequest extends CatalogRequest
{
    protected function prepareForValidation(): void
    {
        $this->prepareMoney('price');
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'categoryId' => [$creating ? 'required' : 'sometimes', 'ulid', 'exists:product_categories,id'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:150'],
            'price' => $this->moneyRules($creating),
            'description' => ['nullable', 'string', 'max:4000'],
            'isActive' => ['sometimes', 'boolean'], 'isAvailable' => ['sometimes', 'boolean'], 'allowNotes' => ['sometimes', 'boolean'],
            'requiresProduction' => ['sometimes', 'boolean'],
            'productionCenterId' => ['nullable', 'ulid', 'exists:production_centers,id'],
        ];
    }
}
