<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Concerns\FederationTeamPageUrlValidationRules;
use App\Models\Season;
use App\Services\AdminSelection;
use App\Services\Ceskyflorbal\FederationTeamPage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Looks up the team a federation team page belongs to, before the team is added to the selected season.
 */
final class LookUpTeamRequest extends FormRequest
{
    use FederationTeamPageUrlValidationRules;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(FederationTeamPage $federationTeamPage): array
    {
        return [
            'federation_team_page_url' => $this->federationTeamPageUrlRules($federationTeamPage),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->federationTeamPageUrlMessages();
    }

    /**
     * Get the season the team is going to be added to: the one the administrator works on.
     */
    public function season(): Season
    {
        return $this->container->make(AdminSelection::class)->season() ?? abort(404);
    }

    /**
     * Answer like the store request while there is no season to add a team to.
     */
    protected function prepareForValidation(): void
    {
        abort_if($this->container->make(AdminSelection::class)->season() === null, 404);
    }
}
