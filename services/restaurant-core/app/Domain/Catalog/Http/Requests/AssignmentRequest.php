<?php

namespace App\Domain\Catalog\Http\Requests;

class AssignmentRequest extends CatalogRequest
{
    public function rules(): array
    {
        return ['modifierGroupId' => ['required', 'ulid', 'exists:modifier_groups,id'], 'minSelect' => ['required', 'integer', 'min:0'], 'maxSelect' => ['required', 'integer', 'min:1', 'gte:minSelect'], 'isActive' => ['sometimes', 'boolean']];
    }
}
