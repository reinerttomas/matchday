<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Season;
use App\Models\TeamSeason;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class Ceskyflorbal
{
    public const string FIXTURE_LIST_URL = 'https://www.ceskyflorbal.cz/team/detail/matches/45019';

    public const string MATCH_DETAIL_URL = 'https://www.ceskyflorbal.cz/match/detail/info/';

    /**
     * Federation arena IDs of the venues in the fixture list snapshot; only Klimeška's 602 is real, the rest are made up.
     *
     * @var array<string, int>
     */
    public const array ARENA_IDS = [
        'Unihoc Aréna Praha' => 101,
        'SH Stochov' => 102,
        'SH TJ Kobylisy' => 103,
        'SH Kutná Hora Klimeška' => 602,
        'Marsch Arena' => 104,
        'SH Kutná Hora Klimeška II' => 105,
        'SH Čakovice' => 106,
        'SH Gymnázium Sedlčany' => 107,
        'SH ZŠ Černošice-Mokropsy' => 108,
        'Sport Eden Beroun' => 109,
    ];

    /**
     * FBC Kutná Hora B in 2026/27, whose live fixture list page was saved as the snapshot.
     */
    public static function kutnaHoraTeamSeason(): TeamSeason
    {
        return TeamSeason::factory()
            ->for(Season::factory()->current()->state(['name' => '2026/27']))
            ->notImported()
            ->create([
                'external_id' => 45019,
                'source_url' => self::FIXTURE_LIST_URL,
            ]);
    }

    /**
     * The live fixture list page of FBC Kutná Hora B in 2026/27.
     */
    public static function fixtureListSnapshot(): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/ceskyflorbal/team-matches-45019.html'));
    }

    /**
     * The live match detail page of fixture 1306754 at SH Kutná Hora Klimeška (arena 602), optionally showing another venue.
     */
    public static function matchDetailSnapshot(string $venueName = 'SH Kutná Hora Klimeška', int $venueExternalId = 602): string
    {
        return Str::of((string) file_get_contents(base_path('tests/Fixtures/ceskyflorbal/match-info-1306754.html')))
            ->replace('href="/arena/detail/default/602"', "href=\"/arena/detail/default/{$venueExternalId}\"")
            ->replace('<h3>SH Kutná Hora Klimeška</h3>', "<h3>{$venueName}</h3>")
            ->toString();
    }

    /**
     * Fake ceskyflorbal.cz: the fixture list page answers as given, and every match detail page shows the venue its row shows in the fixture list snapshot.
     *
     * @param  array<string, mixed>  $matchDetailPages  stubs for single match detail pages, which take precedence
     */
    public static function fake(mixed $fixtureListPage, array $matchDetailPages = []): void
    {
        Http::fake([
            self::FIXTURE_LIST_URL => $fixtureListPage,
            ...$matchDetailPages,
            self::MATCH_DETAIL_URL.'*' => function (Request $request) {
                $row = Str::of(self::fixtureListSnapshot())
                    ->explode('<div class="Match">')
                    ->first(fn (string $row): bool => str_contains($row, '/match/detail/default/'.Str::afterLast($request->url(), '/').'"'));
                $venueName = Str::betweenFirst($row, '<p class="Match-place">', '</p>');

                return Http::response(self::matchDetailSnapshot($venueName, self::ARENA_IDS[$venueName]));
            },
        ]);
    }

    /**
     * The match detail pages requested so far.
     *
     * @return list<string>
     */
    public static function requestedMatchDetailUrls(): array
    {
        return Http::recorded(fn (Request $request): bool => str_starts_with($request->url(), self::MATCH_DETAIL_URL))
            ->map(fn (array $exchange): string => $exchange[0]->url())
            ->values()
            ->all();
    }
}
