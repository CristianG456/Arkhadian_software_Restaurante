<?php

namespace App\Domain\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['targetTableId' => ['required', 'ulid', 'exists:restaurant_tables,id'], 'reason' => ['nullable', 'string', 'max:500']];
    }
}
