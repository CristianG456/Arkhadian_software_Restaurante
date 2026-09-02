<?php

namespace App\Domain\Catalog\Http\Requests;

class CategoryRequest extends CatalogRequest
{
    public function rules(): array
    {
        return ['name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:2000'], 'isActive' => ['sometimes', 'boolean']];
    }
}
