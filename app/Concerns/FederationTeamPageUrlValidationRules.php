<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\TeamSeason;
use App\Services\Ceskyflorbal\FederationTeamPage;
use Closure;

/**
 * The rules the address of a federation team page meets whether a team is looked up or added, with their Czech messages.
 */
trait FederationTeamPageUrlValidationRules
{
    /**
     * Get the validation rules used to validate the address of a federation team page.
     *
     * @return list<string|Closure>
     */
    protected function federationTeamPageUrlRules(FederationTeamPage $federationTeamPage): array
    {
        return [
            'bail',
            'required',
            'string',
            function (string $attribute, string $value, Closure $fail) use ($federationTeamPage): void {
                $teamId = $federationTeamPage->teamId($value);

                if ($teamId === null) {
                    $fail(__('team_seasons.validation.federation_team_page_url_format'));

                    return;
                }

                // The federation gives a team a new ID every season, so an ID already in use usually means the previous season's address was left unchanged.
                $teamSeason = TeamSeason::query()->with(['team', 'season'])->where('external_id', $teamId)->first();

                if ($teamSeason !== null) {
                    $fail(__('team_seasons.validation.federation_team_page_url_taken', ['team_season' => $teamSeason->displayNameWithSeason()]));
                }
            },
        ];
    }

    /**
     * Get the messages of the federation team page address rules, for an address sent as the "federation_team_page_url" field.
     *
     * @return array<string, string>
     */
    protected function federationTeamPageUrlMessages(): array
    {
        return [
            'federation_team_page_url.required' => __('team_seasons.validation.federation_team_page_url_required'),
            'federation_team_page_url.string' => __('team_seasons.validation.federation_team_page_url_format'),
        ];
    }
}
