<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Revision;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Formats fixtures and their revisions the same way wherever the app shows them: the public team page, the admin pages and the change summary.
 *
 * @phpstan-type MatchupPart array{text: string, isOurTeam: bool}
 * @phpstan-type RevisionPart array{text: string, kind: 'text'|'field'|'old_value'|'new_value'|'added'}
 */
final readonly class FixtureFormatter
{
    /**
     * Name a fixture's day by its short weekday and date, such as "NE 4. 10.".
     */
    public function day(CarbonInterface $date): string
    {
        return __('fixtures.day', [
            'weekday' => mb_strtoupper($date->isoFormat('dd')),
            'date' => $date->format('j. n.'),
        ]);
    }

    /**
     * Format a date in full, such as "4. 10. 2026".
     */
    public function date(CarbonInterface $date): string
    {
        return $date->format('j. n. Y');
    }

    /**
     * Format an "HH:MM" or "HH:MM:SS" time the Czech way, without a leading zero: "9:00".
     */
    public function time(string $time): string
    {
        return (int) mb_substr($time, 0, 2).':'.mb_substr($time, 3, 2);
    }

    /**
     * Format a home:away score, or return null unless both sides have one.
     */
    public function score(int|string|null $homeScore, int|string|null $awayScore): ?string
    {
        if ($homeScore === null || $awayScore === null) {
            return null;
        }

        return __('fixtures.score', ['home_score' => $homeScore, 'away_score' => $awayScore]);
    }

    /**
     * Name the fixture's sides in "Home – Away" order as parts, so a page can set our team apart while the wording stays in the translation file.
     *
     * @return list<MatchupPart>
     */
    public function matchup(Fixture $fixture): array
    {
        $parts = preg_split('/(:home\b|:away\b)/', __('fixtures.matchup'), flags: PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        return array_map(fn (string $part): array => match ($part) {
            ':home' => ['text' => $fixture->homeTeamName(), 'isOurTeam' => $fixture->is_home],
            ':away' => ['text' => $fixture->awayTeamName(), 'isOurTeam' => ! $fixture->is_home],
            default => ['text' => $part, 'isOurTeam' => false],
        }, $parts);
    }

    /**
     * Describe a revision as its field's old → new value, such as "Čas: TBD → 19:00", or as a new fixture when it records one.
     */
    public function revision(Revision $revision): string
    {
        return implode('', array_column($this->revisionParts($revision), 'text'));
    }

    /**
     * Describe a revision as parts, so a page can strike through the old value while the wording stays in the translation file.
     *
     * @return list<RevisionPart>
     */
    public function revisionParts(Revision $revision): array
    {
        if ($revision->field === null) {
            return [['text' => __('revisions.fixture_added'), 'kind' => 'added']];
        }

        $values = [
            ':field' => ['text' => $revision->field->label(), 'kind' => 'field'],
            ':old_value' => ['text' => $this->revisionValue($revision->field, $revision->old_value), 'kind' => 'old_value'],
            ':new_value' => ['text' => $this->revisionValue($revision->field, $revision->new_value), 'kind' => 'new_value'],
        ];
        $parts = preg_split('/(:field\b|:old_value\b|:new_value\b)/', __('revisions.field_change'), flags: PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        return array_map(fn (string $part): array => $values[$part] ?? ['text' => $part, 'kind' => 'text'], $parts);
    }

    /**
     * Format a value as a revision stores it (ISO date, "HH:MM", venue name, status value, "0"/"1") for display.
     */
    private function revisionValue(RevisionField $field, ?string $value): string
    {
        if ($value === null) {
            return $field === RevisionField::Time ? __('revisions.values.tbd') : __('revisions.values.empty');
        }

        return match ($field) {
            RevisionField::Date => $this->date(CarbonImmutable::parse($value)),
            RevisionField::Time => $this->time($value),
            RevisionField::Status => FixtureStatus::from($value)->label(),
            RevisionField::IsRescheduled => $value === '1' ? __('revisions.values.yes') : __('revisions.values.no'),
            RevisionField::Venue, RevisionField::HomeScore, RevisionField::AwayScore => $value,
        };
    }
}
