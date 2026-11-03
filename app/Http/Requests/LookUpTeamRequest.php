<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\CategoryValidationRules;
use App\Concerns\FederationTeamPageUrlValidationRules;
use App\Enums\Category;
use App\Models\Season;
use App\Services\AdminSelection;
use App\Services\Ceskyflorbal\FederationTeamPage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Looks up the team a federation team page belongs to, before the team is added to the selected season.
 *
 * The dialog passes the category once the administrator has chosen one, so the team to carry over follows that choice instead of the guess.
 */
final class LookUpTeamRequest extends FormRequest
{
    use CategoryValidationRules;
    use FederationTeamPageUrlValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(FederationTeamPage $federationTeamPage): array
    {
        return [
            'federation_team_page_url' => $this->federationTeamPageUrlRules($federationTeamPage),
            'category' => ['nullable', ...$this->categoryRules()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->federationTeamPageUrlMessages(),
            ...$this->categoryMessages(),
        ];
    }

    /**
     * Get the season the team is going to be added to: the one the administrator works on.
     */
    public function season(): Season
    {
        return $this->container->make(AdminSelection::class)->season() ?? abort(404);
    }

    /**
     * Get the category the administrator chose in the dialog, or null to go by the guess from the competition.
     */
    public function category(): ?Category
    {
        return $this->enum('category', Category::class);
    }

    /**
     * Answer like the store request while there is no season to add a team to.
     */
    protected function prepareForValidation(): void
    {
        abort_if($this->container->make(AdminSelection::class)->season() === null, 404);
    }
}
