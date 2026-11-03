<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\Category;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * The rules a team's category meets whether the team is looked up, added or edited, with their Czech messages.
 */
trait CategoryValidationRules
{
    /**
     * Get the validation rules used to validate a category; the request says whether one must be chosen.
     *
     * @return list<Enum>
     */
    protected function categoryRules(): array
    {
        return [Rule::enum(Category::class)];
    }

    /**
     * Get the messages of the category rules, for a category sent as the "category" field.
     *
     * @return array<string, string>
     */
    protected function categoryMessages(): array
    {
        return [
            'category.required' => __('teams.validation.category_required'),
            'category.enum' => __('teams.validation.category_invalid'),
        ];
    }
}
