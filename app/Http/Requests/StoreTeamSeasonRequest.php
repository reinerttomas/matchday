<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\CategoryValidationRules;
use App\Concerns\FederationTeamPageUrlValidationRules;
use App\Concerns\TeamNameValidationRules;
use App\Enums\Category;
use App\Models\Season;
use App\Models\Team;
use App\Services\AdminSelection;
use App\Services\Ceskyflorbal\FederationTeamPage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * Adds a team to the selected season: a brand-new team by its name, or a team from a previous season by its ID.
 */
final class StoreTeamSeasonRequest extends FormRequest
{
    use CategoryValidationRules;
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
                    if (Str::slug($value) === '') {
                        $fail(__('teams.validation.name_without_slug'));

                        return;
                    }

                    $category = $this->enum('category', Category::class);

                    if ($category === null) {
                        // The category's own rule reports it; without one there is no slug to check yet.
                        return;
                    }

                    $slug = $this->slug($value, $category);

                    // No suffix is added to a taken slug, so the calendar address always says which team it belongs to.
                    if (Team::query()->where('slug', $slug)->exists()) {
                        $fail(__('teams.validation.name_taken', ['slug' => $slug]));
                    }
                },
            ],
            // A team carried over keeps its category, as it keeps its name and slug.
            'category' => [Rule::excludeIf($this->filled('team_id')), 'required', ...$this->categoryRules()],
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
            ...$this->categoryMessages(),
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
     * Get the team to carry over from a previous season, or a new unsaved team with the chosen name and category and the slug derived from both.
     */
    public function team(): Team
    {
        if ($this->filled('team_id')) {
            return Team::query()->findOrFail($this->integer('team_id'));
        }

        $name = $this->string('name')->toString();
        $category = $this->enum('category', Category::class) ?? throw new LogicException('A new team is validated to have a category.');

        return new Team(['name' => $name, 'category' => $category, 'slug' => $this->slug($name, $category)]);
    }

    /**
     * Derive a new team's slug from its name and its category's label, such as "fbc-vhst-kutna-hora-zeny" (ADR 0003).
     */
    private function slug(string $name, Category $category): string
    {
        return Str::slug("{$name} {$category->label()}");
    }
}
