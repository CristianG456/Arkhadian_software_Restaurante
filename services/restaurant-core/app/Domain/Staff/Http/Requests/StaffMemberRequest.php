<?php

namespace App\Domain\Staff\Http\Requests;

use App\Support\Text\LabelNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffMemberRequest extends FormRequest
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
        $id = $this->route('staffMember')?->id;

        return [
            'name' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'min:1', 'max:100'],
            'normalizedName' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', Rule::unique('staff_members', 'normalized_name')->ignore($id)],
            'isActive' => ['sometimes', 'boolean'],
            'username' => ['sometimes', 'nullable', 'string', 'min:3', 'max:60', Rule::unique('staff_members', 'username')->ignore($id)],
            'pin' => ['sometimes', 'nullable', 'digits_between:4,8'],
        ];
    }
}
