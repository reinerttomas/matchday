<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\FederationTeamPageUrlValidationRules;
use App\Concerns\TeamNameValidationRules;
use App\Models\Season;
use App\Models\Team;
use App\Services\AdminSelection;
use App\Services\Ceskyflorbal\FederationTeamPage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Adds a team to the selected season: a brand-new team by its name, or a team from a previous season by its ID.
 */
final class StoreTeamSeasonRequest extends FormRequest
{
    use FederationTeamPageUrlValidationRules;
    use TeamNameValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(FederationTeamPage $federationTeamPage): array
    {
        return [
            'team_id' => [
                'bail',
                'nullable',
                'integer',
                Rule::exists('teams', 'id'),
                Rule::unique('team_seasons', 'team_id')->where('season_id', $this->season()->id),
            ],
            'name' => [
                // A team carried over keeps its name and slug, so its calendar address doesn't change.
                Rule::excludeIf($this->filled('team_id')),
                'bail',
                ...$this->teamNameRules(),
                function (string $attribute, string $value, Closure $fail): void {
                    $slug = Str::slug($value);

                    if ($slug === '') {
                        $fail(__('teams.validation.name_without_slug'));

                        return;
                    }

                    // No suffix is added to a taken slug, so the calendar address always follows the name the administrator chose.
                    if (Team::query()->where('slug', $slug)->exists()) {
                        $fail(__('teams.validation.name_taken', ['slug' => $slug]));
                    }
                },
            ],
            'federation_team_page_url' => $this->federationTeamPageUrlRules($federationTeamPage),
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
            'team_id.integer' => __('team_seasons.validation.team_unknown'),
            'team_id.exists' => __('team_seasons.validation.team_unknown'),
            'team_id.unique' => __('team_seasons.validation.team_in_season', ['season' => $this->season()->name]),
            ...$this->teamNameMessages(),
            ...$this->federationTeamPageUrlMessages(),
        ];
    }

    /**
     * Get the season the team is added to: the one the administrator works on.
     */
    public function season(): Season
    {
        return $this->container->make(AdminSelection::class)->season() ?? abort(404);
    }

    /**
     * Get the team to carry over from a previous season, or a new unsaved team with the chosen name and the slug derived from it.
     */
    public function team(): Team
    {
        if ($this->filled('team_id')) {
            return Team::query()->findOrFail($this->integer('team_id'));
        }

        $name = $this->string('name')->toString();

        return new Team(['name' => $name, 'slug' => Str::slug($name)]);
    }

    /**
     * Get the federation's team ID from the address of the federation team page.
     */
    public function externalId(): int
    {
        return (int) $this->container->make(FederationTeamPage::class)->teamId($this->string('federation_team_page_url')->toString());
    }
}
