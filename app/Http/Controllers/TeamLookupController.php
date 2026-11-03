<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LookUpTeamRequest;
use App\Services\CarryOverTeamMatcher;
use App\Services\CategoryGuesser;
use App\Services\Ceskyflorbal\CeskyflorbalClient;
use App\Services\Ceskyflorbal\FederationTeamPage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

final readonly class TeamLookupController
{
    /**
     * Download the fixture list of the pasted federation team page and return the team's name, competition, guessed category, season and overview address, so the "Přidat tým" dialog can prefill them, together with the team from a previous season it continues.
     *
     * @throws ValidationException when the page belongs to another season than the one the team is added to
     */
    public function __invoke(LookUpTeamRequest $request, CeskyflorbalClient $ceskyflorbal, FederationTeamPage $federationTeamPage, CategoryGuesser $categoryGuesser, CarryOverTeamMatcher $carryOverTeamMatcher): JsonResponse
    {
        $season = $request->season();
        $teamExternalId = $request->teamExternalId();

        try {
            $page = $ceskyflorbal->fixtureList($teamExternalId);
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            // The dialog lets the administrator fill the team in by hand, and the first import reads the page again.
            Log::warning('Looking a team up on ceskyflorbal.cz failed.', [
                'external_id' => $teamExternalId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['message' => __('team_seasons.lookup.unavailable')], Response::HTTP_BAD_GATEWAY);
        }

        if ($page->seasonName !== $season->name) {
            throw ValidationException::withMessages([
                'federation_team_page_url' => __('team_seasons.validation.federation_team_page_url_other_season', [
                    'page_season' => $page->seasonName,
                    'season' => $season->name,
                ]),
            ]);
        }

        $guessedCategory = $page->competitionName === null ? null : $categoryGuesser->guess($page->competitionName);
        // A category the administrator chose replaces the guess for the matching, while the dialog keeps showing what was guessed.
        $category = $request->category() ?? $guessedCategory;

        return response()->json([
            'externalId' => $teamExternalId,
            'name' => $page->teamName,
            'competition' => $page->competitionName,
            'category' => $guessedCategory?->props(),
            'carryOverTeamId' => $page->teamName === null || $category === null
                ? null
                : $carryOverTeamMatcher->match($season, $page->teamName, $category)?->id,
            'season' => $page->seasonName,
            'overviewUrl' => $federationTeamPage->overviewAddress($teamExternalId),
        ]);
    }
}
