<?php

namespace App\Domain\Production\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignPrinterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['printerId' => ['required', 'ulid', 'exists:printers,id'], 'priority' => ['sometimes', 'integer', 'between:1,10']];
    }
}
