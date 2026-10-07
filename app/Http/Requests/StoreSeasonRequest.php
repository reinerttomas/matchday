<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSeasonRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'bail',
                'required',
                'string',
                function (string $attribute, string $value, Closure $fail): void {
                    if (! $this->isTwoConsecutiveYears($value)) {
                        $fail(__('seasons.validation.name_format'));
                    }
                },
                Rule::unique('seasons', 'name'),
            ],
        ];
    }

    /**
     * Get the custom messages for the validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('seasons.validation.name_required'),
            'name.string' => __('seasons.validation.name_format'),
            'name.unique' => __('seasons.validation.name_taken'),
        ];
    }

    /**
     * Determine whether the name is a playing year such as 2027/28: a year, a slash and the last two digits of the next year.
     */
    private function isTwoConsecutiveYears(string $name): bool
    {
        if (preg_match('#^(\d{4})/(\d{2})$#', $name, $matches) !== 1) {
            return false;
        }

        return ((int) $matches[1] + 1) % 100 === (int) $matches[2];
    }
}
