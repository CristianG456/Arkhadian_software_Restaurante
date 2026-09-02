<?php

namespace App\Domain\Catalog\Http\Requests;

class ModifierRequest extends CatalogRequest
{
    protected function prepareForValidation(): void
    {
        $this->prepareMoney('priceDelta');
    }

    public function rules(): array
    {
        return [
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:100'],
            'priceDelta' => $this->moneyRules(false),
            'isActive' => ['sometimes', 'boolean'],
        ];
    }
}
