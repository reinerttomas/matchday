<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Season;
use App\Models\TeamSeason;
use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * @phpstan-type SeasonProps array{id: int, name: string, isCurrent: bool}
 * @phpstan-type TeamSeasonProps array{id: int, name: string, teamSlug: string}
 * @phpstan-type SelectionProps array{season: SeasonProps|null, teamSeason: TeamSeasonProps|null, seasons: list<SeasonProps>, teamSeasons: list<TeamSeasonProps>}
 */
final readonly class AdminSelection
{
    private const string SEASON_KEY = 'admin_selection.season_id';

    private const string TEAM_SEASON_KEY = 'admin_selection.team_season_id';

    /**
     * Create a new instance.
     */
    public function __construct(
        private Session $session,
        private Request $request,
    ) {}

    /**
     * Get the season the administrator works on: the one they picked, or the current season until they pick one.
     */
    public function season(): ?Season
    {
        $seasonId = $this->session->get(self::SEASON_KEY);

        return $this->remember('season.'.(is_int($seasonId) ? $seasonId : 'default'), function () use ($seasonId): ?Season {
            $selectedSeason = is_int($seasonId) ? Season::query()->find($seasonId) : null;

            return $selectedSeason ?? Season::query()
                // Without a current season the newest one stands in, so the admin pages still have a season to show.
                ->orderByDesc('is_current')
                ->orderByDesc('name')
                ->first();
        });
    }

    /**
     * Get the team season the administrator works on: the one they picked in the selected season, or that season's first team season.
     */
    public function teamSeason(): ?TeamSeason
    {
        return $this->selectedTeamSeasonIn($this->teamSeasons());
    }

    /**
     * Get the selected season's team seasons, alphabetically by name, or none when there is no season.
     *
     * @return Collection<int, TeamSeason>
     */
    public function teamSeasons(): Collection
    {
        $season = $this->season();

        return $season === null ? new Collection : $this->teamSeasonsOf($season);
    }

    /**
     * Remember the season and team season the administrator picked, falling back to the season's first team season when they didn't pick one.
     */
    public function select(Season $season, ?TeamSeason $teamSeason = null): void
    {
        $this->session->put(self::SEASON_KEY, $season->id);
        $this->session->put(self::TEAM_SEASON_KEY, ($teamSeason ?? $this->teamSeasonsOf($season)->first())?->id);
    }

    /**
     * Describe the selection and the seasons and team seasons the switcher offers, for every admin page.
     *
     * @return SelectionProps
     */
    public function present(): array
    {
        $season = $this->season();
        $teamSeasons = $this->teamSeasons();
        $teamSeason = $this->selectedTeamSeasonIn($teamSeasons);

        return [
            'season' => $season === null ? null : $this->seasonProps($season),
            'teamSeason' => $teamSeason === null ? null : $this->teamSeasonProps($teamSeason),
            'seasons' => array_values(Season::query()
                ->orderByDesc('name')
                ->get()
                ->map(fn (Season $season): array => $this->seasonProps($season))
                ->all()),
            'teamSeasons' => array_values($teamSeasons
                ->map(fn (TeamSeason $teamSeason): array => $this->teamSeasonProps($teamSeason))
                ->all()),
        ];
    }

    /**
     * Get the season's team seasons in the order the switcher offers them, alphabetically by name.
     *
     * @return Collection<int, TeamSeason>
     */
    private function teamSeasonsOf(Season $season): Collection
    {
        return $this->remember("team_seasons.{$season->id}", fn (): Collection => $season->teamSeasons()
            ->with('team')
            ->orderBy('id')
            ->get()
            ->sortBy(fn (TeamSeason $teamSeason): string => $teamSeason->displayName(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values());
    }

    /**
     * Resolve a value once per request, since both the page's controller and the shared props ask for the selection.
     *
     * @template TValue
     *
     * @param  Closure(): TValue  $resolve
     * @return TValue
     */
    private function remember(string $key, Closure $resolve): mixed
    {
        $key = "admin_selection.{$key}";

        if (! $this->request->attributes->has($key)) {
            $this->request->attributes->set($key, $resolve());
        }

        return $this->request->attributes->get($key);
    }

    /**
     * Pick the remembered team season from the selected season's team seasons, or the first of them when it isn't one of them.
     *
     * @param  Collection<int, TeamSeason>  $teamSeasons
     */
    private function selectedTeamSeasonIn(Collection $teamSeasons): ?TeamSeason
    {
        return $teamSeasons->firstWhere('id', $this->session->get(self::TEAM_SEASON_KEY)) ?? $teamSeasons->first();
    }

    /**
     * Describe a season for the switcher.
     *
     * @return SeasonProps
     */
    private function seasonProps(Season $season): array
    {
        return ['id' => $season->id, 'name' => $season->name, 'isCurrent' => $season->is_current];
    }

    /**
     * Describe a team season for the switcher and the link to its team's public page.
     *
     * @return TeamSeasonProps
     */
    private function teamSeasonProps(TeamSeason $teamSeason): array
    {
        return ['id' => $teamSeason->id, 'name' => $teamSeason->displayName(), 'teamSlug' => $teamSeason->team->slug];
    }
}
