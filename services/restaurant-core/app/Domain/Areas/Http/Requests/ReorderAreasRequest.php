<?php

namespace App\Domain\Areas\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderAreasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'ulid', 'distinct', 'exists:areas,id'],
            'items.*.sortOrder' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
