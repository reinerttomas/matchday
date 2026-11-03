<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\TeamNameValidationRules;
use App\Enums\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Renames a team or changes its category; its slug is never edited, so the name may match another team's slug or name.
 */
final class UpdateTeamRequest extends FormRequest
{
    use TeamNameValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['bail', ...$this->teamNameRules()],
            'category' => ['required', Rule::enum(Category::class)],
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
            ...$this->teamNameMessages(),
            'category.required' => __('teams.validation.category_required'),
            'category.enum' => __('teams.validation.category_invalid'),
        ];
    }
}
