<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\TeamNameValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Renames a team; its slug is never edited, so the name may match another team's slug or name.
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
        ];
    }

    /**
     * Get the custom messages for the validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->teamNameMessages();
    }
}
