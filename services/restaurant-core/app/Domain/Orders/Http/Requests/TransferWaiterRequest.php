<?php

namespace App\Domain\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferWaiterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['waiterId' => ['required', 'ulid', 'exists:staff_members,id'], 'reason' => ['nullable', 'string', 'max:500']];
    }
}
