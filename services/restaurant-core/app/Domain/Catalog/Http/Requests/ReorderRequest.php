<?php

namespace App\Domain\Catalog\Http\Requests;

class ReorderRequest extends CatalogRequest
{
    public function rules(): array
    {
        return ['items' => ['required', 'array', 'min:1'], 'items.*.id' => ['required', 'ulid', 'distinct'], 'items.*.sortOrder' => ['required', 'integer', 'min:1', 'distinct']];
    }
}
