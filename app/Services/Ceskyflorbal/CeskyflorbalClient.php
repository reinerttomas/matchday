<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

use App\Models\TeamSeason;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

final readonly class CeskyflorbalClient
{
    /**
     * The federation's bot protection lets browsers through, so the client presents itself as one.
     */
    private const string USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36';

    /**
     * Create a new instance.
     */
    public function __construct(
        private FixtureListParser $fixtureListParser,
        private MatchDetailParser $matchDetailParser,
        #[Config('services.ceskyflorbal.request_pause_milliseconds')]
        private int $requestPauseMilliseconds,
    ) {}

    /**
     * Download and parse the team season's fixture list page.
     */
    public function fixtureList(TeamSeason $teamSeason): FixtureListPageData
    {
        return $this->fixtureListParser->parse($this->download($teamSeason->source_url), $teamSeason);
    }

    /**
     * Download and parse the match detail page of a fixture, pausing first.
     *
     * A match detail page is always requested after the fixture list or another detail page, so the pause spaces out the requests.
     */
    public function matchDetail(int $fixtureExternalId): MatchDetailPageData
    {
        Sleep::for($this->requestPauseMilliseconds)->milliseconds();

        // The "Informace" tab; the default tab of the match detail shows the venue's name only.
        return $this->matchDetailParser->parse($this->download("https://www.ceskyflorbal.cz/match/detail/info/{$fixtureExternalId}"));
    }

    /**
     * Download the HTML of a page on ceskyflorbal.cz.
     */
    private function download(string $url): string
    {
        return Http::withUserAgent(self::USER_AGENT)
            ->connectTimeout(10)
            ->timeout(30)
            ->get($url)
            ->throw()
            ->body();
    }
}
