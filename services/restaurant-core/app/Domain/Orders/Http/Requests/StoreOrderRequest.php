<?php

namespace App\Domain\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tableId' => ['required', 'ulid', 'exists:restaurant_tables,id'],
            'assignedWaiterId' => ['nullable', 'ulid', 'exists:staff_members,id'],
            'clientOperationId' => ['required', 'ulid'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:productId,variantId,quantity,modifierOptionIds,notes'],
            'items.*.productId' => ['required', 'ulid', 'exists:products,id'],
            'items.*.variantId' => ['nullable', 'ulid', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'between:1,100'],
            'items.*.modifierOptionIds' => ['sometimes', 'array'],
            'items.*.modifierOptionIds.*' => ['ulid', 'distinct'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
