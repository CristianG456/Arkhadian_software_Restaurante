<?php

namespace App\Domain\Tables\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRestaurantTablesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'ulid', 'distinct', 'exists:restaurant_tables,id'],
            'items.*.sortOrder' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }
}
