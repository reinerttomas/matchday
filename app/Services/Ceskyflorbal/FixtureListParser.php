<?php

declare(strict_types=1);

namespace App\Services\Ceskyflorbal;

use App\Enums\FixtureStatus;
use App\Models\TeamSeason;
use Carbon\CarbonImmutable;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use UnexpectedValueException;

final readonly class FixtureListParser
{
    /**
     * Parse the team season's fixture list page from ceskyflorbal.cz.
     *
     * @throws UnexpectedValueException when the team header or a fixture row lacks markup the import cannot do without
     */
    public function parse(string $html, TeamSeason $teamSeason): FixtureListPageData
    {
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $seasonStartYear = (int) Str::before($teamSeason->season->name, '/');

        $rows = [];

        foreach ($document->querySelectorAll('.Match') as $row) {
            $rows[] = $this->parseRow($row, $teamSeason, $seasonStartYear);
        }

        // The page header spells the team name in capitals, so take it from the team's own link in a fixture row.
        $teamName = $this->text($document, ".Match a[href=\"/team/detail/overview/{$teamSeason->external_id}\"] .Match-teamName");

        return new FixtureListPageData(
            seasonName: $this->seasonName($this->text($document, 'h3.ProfileClub-header--city') ?? ''),
            teamName: $teamName,
            competitionName: $this->text($document, '.Matches-body--sectionHeader h3'),
            rows: $rows,
        );
    }

    /**
     * Read the season from the team header, such as "PH A SČ LIGA MUŽŮ 2026/2027", and name it like our seasons ("2026/27").
     */
    private function seasonName(string $header): string
    {
        if (preg_match('/(\d{4})\/\d{2}(\d{2})$/', $header, $matches) !== 1) {
            throw new UnexpectedValueException("Unreadable season in the team header [{$header}].");
        }

        return "{$matches[1]}/{$matches[2]}";
    }

    /**
     * Parse one fixture row.
     *
     * Each row repeats its date and round in a mobile and a desktop wrapper; querySelector() takes the first.
     */
    private function parseRow(Element $row, TeamSeason $teamSeason, int $seasonStartYear): FixtureListRowData
    {
        $externalId = $this->idFromLink($row, 'a[href*="/match/detail/default/"]');
        $isHome = $this->idFromLink($row, '.Match-leftContent a[href*="/team/detail/overview/"]') === $teamSeason->external_id;
        $score = $this->score($this->text($row, '.Match-score'));
        $startTime = $this->text($row, '.Match-startTime');

        return new FixtureListRowData(
            externalId: $externalId,
            round: $this->round($this->text($row, '.Match-round')),
            date: $this->date($this->requiredText($row, '.Match-date'), $seasonStartYear),
            showsStartTime: $startTime !== null,
            time: $this->time($startTime),
            isHome: $isHome,
            opponentName: $this->requiredText($row, $isHome ? '.Match-rightContent .Match-teamName' : '.Match-leftContent .Match-teamName'),
            venueName: $this->text($row, '.Match-place'),
            status: $this->status($row, $teamSeason, $externalId),
            isRescheduled: $row->querySelector('.Tooltip--warning') !== null,
            homeScore: $score[0] ?? null,
            awayScore: $score[1] ?? null,
        );
    }

    /**
     * Read the date of a row such as "NE, 4. 10.", which has no year.
     *
     * A season runs from July to June, so July–December falls in its first year and January–June in its second.
     */
    private function date(string $text, int $seasonStartYear): CarbonImmutable
    {
        if (preg_match('/(\d{1,2})\.\s*(\d{1,2})\.$/', $text, $matches) !== 1) {
            throw new UnexpectedValueException("Unreadable fixture date [{$text}].");
        }

        $day = (int) $matches[1];
        $month = (int) $matches[2];
        $year = $month >= 7 ? $seasonStartYear : $seasonStartYear + 1;

        return CarbonImmutable::create($year, $month, $day);
    }

    /**
     * Read the start time of a row; the federation shows 00:00 until it sets the time.
     */
    private function time(?string $text): ?string
    {
        if ($text === null || $text === '00:00') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})$/', $text, $matches) !== 1) {
            throw new UnexpectedValueException("Unreadable fixture start time [{$text}].");
        }

        return sprintf('%02d:%s:00', $matches[1], $matches[2]);
    }

    /**
     * Read a home:away score; a suffix such as "pp" after the score marks how the fixture was decided.
     *
     * @return array{int, int}|null
     */
    private function score(?string $text): ?array
    {
        if ($text === null) {
            return null;
        }

        if (preg_match('/^(\d+):(\d+)/', $text, $matches) !== 1) {
            throw new UnexpectedValueException("Unreadable fixture score [{$text}].");
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    /**
     * Read the round number from "N. kolo".
     */
    private function round(?string $text): ?int
    {
        return $text !== null && preg_match('/^(\d+)\. kolo$/', $text, $matches) === 1 ? (int) $matches[1] : null;
    }

    /**
     * Read the fixture status: a row shows a venue until the fixture is played, then "odehráno".
     *
     * Markup for postponed and cancelled fixtures has not been seen yet, so any other row keeps the stored status.
     */
    private function status(Element $row, TeamSeason $teamSeason, int $externalId): ?FixtureStatus
    {
        $text = $this->text($row, '.Match-status');
        $showsVenue = $row->querySelector('.Match-place') !== null;

        $status = match (true) {
            $text === 'odehráno' => FixtureStatus::Finished,
            $text === null && $showsVenue => FixtureStatus::Scheduled,
            default => null,
        };

        if ($status === null) {
            Log::warning('Unknown fixture status on the fixture list page.', [
                'team_season_id' => $teamSeason->id,
                'external_id' => $externalId,
                'status' => $text,
            ]);
        }

        return $status;
    }

    /**
     * Read the numeric ID at the end of a link such as "/match/detail/default/1306729".
     */
    private function idFromLink(Element $row, string $selector): int
    {
        $href = (string) $row->querySelector($selector)?->getAttribute('href');

        if (preg_match('/\/(\d+)$/', $href, $matches) !== 1) {
            throw new UnexpectedValueException("Fixture row has no link matching [{$selector}].");
        }

        return (int) $matches[1];
    }

    /**
     * Read the whitespace-normalised text of the first element matching the selector.
     */
    private function text(Element|HTMLDocument $context, string $selector): ?string
    {
        $element = $context->querySelector($selector);

        return $element === null ? null : Str::squish($element->textContent ?? '');
    }

    /**
     * Read the text of an element every fixture row has.
     */
    private function requiredText(Element $row, string $selector): string
    {
        return $this->text($row, $selector) ?? throw new UnexpectedValueException("Fixture row has no [{$selector}].");
    }
}
