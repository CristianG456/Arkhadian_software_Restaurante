<?php

namespace App\Domain\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function moneyRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'string', 'regex:/^(0|[1-9]\d{0,11})(\.\d{1,2})?$/'];
    }

    protected function prepareMoney(string ...$keys): void
    {
        foreach ($keys as $key) {
            if ($this->has($key) && (is_int($this->input($key)) || is_float($this->input($key)))) {
                $this->merge([$key => (string) $this->input($key)]);
            }
        }
    }
}
