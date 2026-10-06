<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class ChangeSummaryWriter
{
    /**
     * The key of the revision recording that a fixture appeared after the initial import, as it has no field.
     */
    private const string ADDED = 'added';

    /**
     * Write the change summary of an import in Czech: one bullet per revised fixture, then a link to the team's public page; or return null when none of its revisions is worth telling the team.
     *
     * A bullet shows each fixture as it was right after the import, so the summary of an old import reads the same after later imports revised its fixtures again. Only the names and which side is home, which no revision records, come from the fixture as it is stored now.
     */
    public function write(Import $import): ?string
    {
        $teamSeason = $import->teamSeason;
        $revisions = $import->revisions()->with('fixture.venue')->get();
        $valuesAfterImport = $this->valuesAfterImport($import, $revisions->pluck('fixture'));

        $bullets = $revisions
            ->groupBy('fixture_id')
            ->map(fn (Collection $fixtureRevisions, int $fixtureId): ?array => $this->bullet(
                $fixtureRevisions->firstOrFail()->fixture,
                $valuesAfterImport[$fixtureId],
                $fixtureRevisions->keyBy(fn (Revision $revision): string => $revision->field->value ?? self::ADDED),
                $teamSeason->displayName(),
            ))
            ->filter()
            ->sort(fn (array $bullet, array $otherBullet): int => [$bullet['date'], $bullet['time'], $bullet['fixtureId']]
                <=> [$otherBullet['date'], $otherBullet['time'], $otherBullet['fixtureId']])
            ->pluck('line');

        if ($bullets->isEmpty()) {
            return null;
        }

        return implode("\n", [
            "📅 Změny v rozpisu {$teamSeason->displayName()}",
            ...$bullets,
            '',
            'Kalendář: '.url("t/{$teamSeason->team->slug}"),
        ]);
    }

    /**
     * Build the WhatsApp link that opens a chat with the change summary prefilled.
     */
    public function whatsAppUrl(string $summary): string
    {
        return 'https://wa.me/?text='.rawurlencode($summary);
    }

    /**
     * Rebuild the revisable values each fixture had right after the import: the stored values, except that a field a later import revised takes the old value of the earliest such revision.
     *
     * @param  Collection<int, Fixture>  $fixtures
     * @return array<int, array<string, string|null>> keyed by the fixture ID, then by the revision field
     */
    private function valuesAfterImport(Import $import, Collection $fixtures): array
    {
        $values = $fixtures->mapWithKeys(fn (Fixture $fixture): array => [$fixture->id => $fixture->revisableValues()])->all();

        // Newest first, so the earliest later revision of a field is applied last.
        $laterRevisions = Revision::query()
            ->whereIn('fixture_id', array_keys($values))
            ->where('import_id', '>', $import->id)
            ->whereNotNull('field')
            ->orderByDesc('import_id')
            ->orderByDesc('id')
            ->get();

        foreach ($laterRevisions as $revision) {
            $values[$revision->fixture_id][$revision->field->value] = $revision->old_value;
        }

        return $values;
    }

    /**
     * Describe the revisions one import recorded for a fixture in one bullet, such as "• NE 15. 11. FBC Kutná Hora B – Las Plantas: čas doplněn 9:00", or return null when none of them is worth telling the team.
     *
     * @param  array<string, string|null>  $values  the fixture's revisable values right after the import
     * @param  Collection<string, Revision>  $revisions  keyed by the revision field, or by "added"
     * @return array{date: string, time: string, fixtureId: int, line: string}|null
     */
    private function bullet(Fixture $fixture, array $values, Collection $revisions, string $teamName): ?array
    {
        $date = CarbonImmutable::parse((string) $values[RevisionField::Date->value]);
        $teams = $fixture->is_home ? "{$teamName} – {$fixture->opponent_name}" : "{$fixture->opponent_name} – {$teamName}";

        $descriptions = $revisions->has(self::ADDED) ? [$this->describeAddition($values)] : array_filter([
            $this->describeStatus($revisions->get(RevisionField::Status->value), $values),
            $this->describeScore($revisions, $values),
            $this->describeDate($revisions->get(RevisionField::Date->value)),
            $this->describeTime($revisions->get(RevisionField::Time->value)),
            $this->describeVenue($revisions->get(RevisionField::Venue->value)),
            $this->describeRescheduled($revisions),
        ]);

        if ($descriptions === []) {
            return null;
        }

        return [
            'date' => $date->toDateString(),
            'time' => $values[RevisionField::Time->value] ?? '',
            'fixtureId' => $fixture->id,
            // Weekday names come from Carbon's own Czech translation, so neither the app locale nor the system locale changes them.
            'line' => '• '.mb_strtoupper($date->settings(['locale' => 'cs'])->isoFormat('dd'))." {$date->format('j. n.')} {$teams}: ".implode('; ', $descriptions),
        ];
    }

    /**
     * Describe a fixture that appeared after the initial import by its time and venue, or by its status when it isn't scheduled.
     *
     * @param  array<string, string|null>  $values
     */
    private function describeAddition(array $values): string
    {
        $status = FixtureStatus::from((string) $values[RevisionField::Status->value]);
        $time = $values[RevisionField::Time->value];

        $details = $status === FixtureStatus::Scheduled
            ? [$time === null ? 'čas TBD' : $this->formatTime($time), $values[RevisionField::Venue->value]]
            : [$this->describeStatusChange($status, null, $values)];

        return implode(', ', ['nový zápas v rozpisu', ...array_filter($details)]);
    }

    /**
     * Describe a status change of a fixture.
     *
     * @param  array<string, string|null>  $values
     */
    private function describeStatus(?Revision $status, array $values): ?string
    {
        return $status === null ? null : $this->describeStatusChange(FixtureStatus::from((string) $status->new_value), $status->old_value, $values);
    }

    /**
     * Describe the status a fixture moved to; a finished fixture comes with its score.
     *
     * @param  array<string, string|null>  $values
     */
    private function describeStatusChange(FixtureStatus $status, ?string $statusBefore, array $values): string
    {
        return match ($status) {
            FixtureStatus::Scheduled => $statusBefore === FixtureStatus::Cancelled->value ? 'znovu v rozpisu' : 'znovu naplánováno',
            FixtureStatus::Postponed => 'odloženo, nový termín zatím není známý',
            FixtureStatus::Finished => mb_trim('odehráno '.$this->formatScore($values[RevisionField::HomeScore->value], $values[RevisionField::AwayScore->value])),
            FixtureStatus::Cancelled => 'zrušeno',
        };
    }

    /**
     * Describe a score that changed without the status changing, such as a corrected result.
     *
     * @param  Collection<string, Revision>  $revisions
     * @param  array<string, string|null>  $values
     */
    private function describeScore(Collection $revisions, array $values): ?string
    {
        $homeScore = $revisions->get(RevisionField::HomeScore->value);
        $awayScore = $revisions->get(RevisionField::AwayScore->value);

        if ($revisions->has(RevisionField::Status->value) || ($homeScore === null && $awayScore === null)) {
            return null;
        }

        $homeScoreAfter = $values[RevisionField::HomeScore->value];
        $awayScoreAfter = $values[RevisionField::AwayScore->value];
        $scoreAfter = $this->formatScore($homeScoreAfter, $awayScoreAfter);
        // A side whose score didn't change has no revision, so its score before is its score after.
        $scoreBefore = $this->formatScore(
            $homeScore === null ? $homeScoreAfter : $homeScore->old_value,
            $awayScore === null ? $awayScoreAfter : $awayScore->old_value,
        );

        return match (true) {
            $scoreBefore === null && $scoreAfter === null => null,
            $scoreBefore === null => "skóre {$scoreAfter}",
            $scoreAfter === null => "skóre odstraněno (původně {$scoreBefore})",
            default => "opravené skóre {$scoreAfter} (původně {$scoreBefore})",
        };
    }

    /**
     * Describe a fixture moved to another date, with both dates in full.
     */
    private function describeDate(?Revision $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $dateAfter = CarbonImmutable::parse((string) $date->new_value)->format('j. n. Y');
        $dateBefore = CarbonImmutable::parse((string) $date->old_value)->format('j. n. Y');

        return "přeloženo, nový termín {$dateAfter} (původně {$dateBefore})";
    }

    /**
     * Describe a start time that was set, changed or went back to TBD.
     */
    private function describeTime(?Revision $time): ?string
    {
        return match (true) {
            $time === null => null,
            $time->new_value === null => 'čas TBD (původně '.$this->formatTime((string) $time->old_value).')',
            $time->old_value === null => 'čas doplněn '.$this->formatTime($time->new_value),
            default => 'nový čas '.$this->formatTime($time->new_value).' (původně '.$this->formatTime($time->old_value).')',
        };
    }

    /**
     * Describe a fixture moved to another venue.
     */
    private function describeVenue(?Revision $venue): ?string
    {
        return match (true) {
            $venue === null => null,
            $venue->old_value === null => "hala doplněna {$venue->new_value}",
            $venue->new_value === null => "hala neuvedena (původně {$venue->old_value})",
            default => "nová hala {$venue->new_value} (původně {$venue->old_value})",
        };
    }

    /**
     * Describe a fixture that became a rescheduled fixture; one that stopped being one isn't worth telling the team.
     *
     * A rescheduled fixture usually moves to its replacement date in the same import, and the moved date already says so.
     *
     * @param  Collection<string, Revision>  $revisions
     */
    private function describeRescheduled(Collection $revisions): ?string
    {
        $isRescheduled = $revisions->get(RevisionField::IsRescheduled->value);

        return match (true) {
            $isRescheduled === null, $isRescheduled->new_value === '0', $revisions->has(RevisionField::Date->value) => null,
            default => 'nově dohrávka',
        };
    }

    /**
     * Format a home:away score, or return null unless both sides have one.
     */
    private function formatScore(?string $homeScore, ?string $awayScore): ?string
    {
        return $homeScore === null || $awayScore === null ? null : "{$homeScore}:{$awayScore}";
    }

    /**
     * Format an "HH:MM" time the Czech way, without a leading zero: "9:00".
     */
    private function formatTime(string $time): string
    {
        return (int) mb_substr($time, 0, 2).':'.mb_substr($time, 3, 2);
    }
}
