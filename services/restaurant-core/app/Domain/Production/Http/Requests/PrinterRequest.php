<?php

namespace App\Domain\Production\Http\Requests;

use App\Support\Text\LabelNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrinterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $name = LabelNormalizer::display($this->input('name'));
            $this->merge(['name' => $name, 'normalizedName' => LabelNormalizer::comparable($name)]);
        }
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return ['name' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'], 'normalizedName' => [$creating ? 'required' : 'sometimes', Rule::unique('printers', 'normalized_name')->ignore($this->route('printer')?->id)], 'driverMode' => [$creating ? 'required' : 'sometimes', Rule::in(['TEST_FILE', 'WINDOWS_RAW_SHARE'])], 'connectionType' => [$creating ? 'required' : 'sometimes', Rule::in(['FILE', 'WINDOWS_SHARE', 'USB', 'SERIAL', 'PARALLEL'])], 'systemName' => ['nullable', 'string', 'max:180', 'regex:/^[A-Za-z0-9 _.-]+$/'], 'devicePath' => ['nullable', 'string', 'max:255'], 'paperWidth' => ['sometimes', Rule::in([58, 80])], 'isActive' => ['sometimes', 'boolean'], 'isDefaultFallback' => ['sometimes', 'boolean'], 'isPreaccountDefault' => ['sometimes', 'boolean']];
    }
}
