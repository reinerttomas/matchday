<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAdminSelectionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'season_id' => ['required', 'integer', Rule::exists('seasons', 'id')],
            // Only a team season of the chosen season can be selected.
            'team_season_id' => ['nullable', 'integer', Rule::exists('team_seasons', 'id')->where('season_id', $this->integer('season_id'))],
        ];
    }
}
