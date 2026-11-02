<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FixtureStatus;
use App\Enums\RevisionField;
use Database\Factories\FixtureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read int $team_season_id
 * @property-read int $external_id
 * @property-read int|null $round
 * @property-read bool $is_home
 * @property-read string $opponent_name
 * @property-read int|null $venue_id
 * @property-read Carbon $date
 * @property-read string|null $time
 * @property-read FixtureStatus $status
 * @property-read bool $is_rescheduled
 * @property-read int|null $home_score
 * @property-read int|null $away_score
 * @property-read int $sequence
 * @property-read int $missing_count
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read TeamSeason $teamSeason
 * @property-read Venue|null $venue
 * @property-read Collection<int, Revision> $revisions
 */
#[Fillable([
    'team_season_id',
    'external_id',
    'round',
    'is_home',
    'opponent_name',
    'venue_id',
    'date',
    'time',
    'status',
    'is_rescheduled',
    'home_score',
    'away_score',
    'sequence',
    'missing_count',
])]
final class Fixture extends Model
{
    /** @use HasFactory<FixtureFactory> */
    use HasFactory;

    /**
     * Get the team season whose fixture list contains the fixture.
     *
     * @return BelongsTo<TeamSeason, $this>
     */
    public function teamSeason(): BelongsTo
    {
        return $this->belongsTo(TeamSeason::class);
    }

    /**
     * Get the venue the fixture is played at.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the recorded revisions of the fixture.
     *
     * @return HasMany<Revision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    /**
     * Name the home side: our team season when the fixture is at home, otherwise the opponent.
     */
    public function homeTeamName(): string
    {
        return $this->is_home ? $this->teamSeason->displayName() : $this->opponent_name;
    }

    /**
     * Name the away side: the opponent when the fixture is at home, otherwise our team season.
     */
    public function awayTeamName(): string
    {
        return $this->is_home ? $this->opponent_name : $this->teamSeason->displayName();
    }

    /**
     * Name the fixture's round, or the round it makes up for when it is rescheduled, so the out-of-order date makes sense.
     */
    public function roundLabel(): ?string
    {
        if ($this->round === null) {
            return null;
        }

        return $this->is_rescheduled
            ? __('fixtures.rounds.rescheduled', ['round' => $this->round])
            : __('fixtures.rounds.default', ['round' => $this->round]);
    }

    /**
     * Count the fixture as missing from one more fixture list, cancelling it once it is missing from two, so a one-off glitch at the source cancels nothing.
     */
    public function countAsMissing(): void
    {
        $this->setAttribute('missing_count', $this->missing_count + 1);

        if ($this->missing_count >= 2) {
            $this->setAttribute('status', FixtureStatus::Cancelled);
        }
    }

    /**
     * Move the fixture to its next sequence, so calendar clients take the changed event over the copy they hold.
     */
    public function moveToNextSequence(): void
    {
        $this->setAttribute('sequence', $this->sequence + 1);
    }

    /**
     * Read the fixture's revisable fields as revisions store them, keyed by the revision field.
     *
     * @return array{date: string, time: string|null, venue: string|null, status: string, is_rescheduled: string, home_score: string|null, away_score: string|null}
     */
    public function revisableValues(): array
    {
        return [
            RevisionField::Date->value => $this->date->toDateString(),
            RevisionField::Time->value => $this->time === null ? null : mb_substr($this->time, 0, 5),
            RevisionField::Venue->value => $this->venue?->name,
            RevisionField::Status->value => $this->status->value,
            RevisionField::IsRescheduled->value => $this->is_rescheduled ? '1' : '0',
            RevisionField::HomeScore->value => $this->home_score === null ? null : (string) $this->home_score,
            RevisionField::AwayScore->value => $this->away_score === null ? null : (string) $this->away_score,
        ];
    }

    /**
     * Read the fixture's revisable values as they were right after the import: the stored values, except that a field a later import revised takes the old value of the earliest such revision.
     *
     * It reads the fixture's revisions relation, so callers that do this for many fixtures eager load it.
     *
     * @return array<string, string|null> keyed by the revision field
     */
    public function revisableValuesAfter(Import $import): array
    {
        $values = $this->revisableValues();

        // Newest first, so the earliest later revision of a field is applied last.
        $laterRevisions = $this->revisions
            ->filter(fn (Revision $revision): bool => $revision->import_id > $import->id)
            ->sortBy([['import_id', 'desc'], ['id', 'desc']]);

        foreach ($laterRevisions as $revision) {
            if ($revision->field !== null) {
                $values[$revision->field->value] = $revision->old_value;
            }
        }

        return $values;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
            'is_home' => 'boolean',
            'date' => 'date',
            'status' => FixtureStatus::class,
            'is_rescheduled' => 'boolean',
        ];
    }
}
