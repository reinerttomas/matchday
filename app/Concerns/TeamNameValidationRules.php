<?php

declare(strict_types=1);

namespace App\Concerns;

/**
 * The rules a team's name meets whether the team is added or renamed, with their Czech messages.
 */
trait TeamNameValidationRules
{
    /**
     * Get the validation rules used to validate a team's name.
     *
     * @return list<string>
     */
    protected function teamNameRules(): array
    {
        return ['required', 'string', 'max:100'];
    }

    /**
     * Get the messages of the team name rules, for a team name sent as the "name" field.
     *
     * @return array<string, string>
     */
    protected function teamNameMessages(): array
    {
        return [
            'name.required' => __('teams.validation.name_required'),
            'name.string' => __('teams.validation.name_format'),
            'name.max' => __('teams.validation.name_too_long'),
        ];
    }
}
