<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ImportStatus;
use Database\Factories\TeamSeasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $season_id
 * @property int $external_id
 * @property string $source_url
 * @property string|null $name
 * @property string|null $competition_name
 * @property bool $auto_import_enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read Season $season
 * @property-read Collection<int, Fixture> $fixtures
 * @property-read Collection<int, Import> $imports
 * @property-read Import|null $initialImport
 * @property-read Import|null $lastImport
 */
#[Fillable(['team_id', 'season_id', 'external_id', 'source_url', 'name', 'competition_name', 'auto_import_enabled'])]
final class TeamSeason extends Model
{
    /** @use HasFactory<TeamSeasonFactory> */
    use HasFactory;

    /**
     * Get the team taking part in the season.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the season the team takes part in.
     *
     * @return BelongsTo<Season, $this>
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Get the fixtures in the team season's fixture list.
     *
     * @return HasMany<Fixture, $this>
     */
    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }

    /**
     * Get the imports of the team season's fixture list.
     *
     * @return HasMany<Import, $this>
     */
    public function imports(): HasMany
    {
        return $this->hasMany(Import::class);
    }

    /**
     * Get the team season's initial import: its first successful one, whose additions are never announced to the team.
     *
     * @return HasOne<Import, $this>
     */
    public function initialImport(): HasOne
    {
        return $this->imports()->one()->ofMany(
            ['started_at' => 'min', 'id' => 'min'],
            fn (Builder $query): Builder => $query->where('status', ImportStatus::Ok),
        );
    }

    /**
     * Get the team season's latest import that has ended, whether ok, error or aborted; a running import has no outcome to show yet.
     *
     * @return HasOne<Import, $this>
     */
    public function lastImport(): HasOne
    {
        return $this->imports()->one()->ofMany(
            ['started_at' => 'max', 'id' => 'max'],
            fn (Builder $query): Builder => $query->whereNot('status', ImportStatus::Running),
        );
    }

    /**
     * Get the team season's imports that recorded revisions to announce to the team: every one with revisions except the initial import.
     *
     * The initial import is left out by requiring an earlier successful import rather than by its ID, so the relation also holds when eager loaded.
     *
     * @return HasMany<Import, $this>
     */
    public function revisingImports(): HasMany
    {
        return $this->imports()
            ->whereHas('revisions')
            ->whereExists(fn (QueryBuilder $earlierImports): QueryBuilder => $earlierImports
                ->from('imports', 'earlier_imports')
                ->whereColumn('earlier_imports.team_season_id', 'imports.team_season_id')
                ->where('earlier_imports.status', ImportStatus::Ok)
                ->where(fn (QueryBuilder $earlier): QueryBuilder => $earlier
                    ->whereColumn('earlier_imports.started_at', '<', 'imports.started_at')
                    ->orWhere(fn (QueryBuilder $sameStart): QueryBuilder => $sameStart
                        ->whereColumn('earlier_imports.started_at', 'imports.started_at')
                        ->whereColumn('earlier_imports.id', '<', 'imports.id'))));
    }

    /**
     * Name the team season by its team's name, such as "FBC Kutná Hora B".
     *
     * The name the import reads from ceskyflorbal.cz stays on the team season; the app shows the name the administrator gave the team.
     */
    public function displayName(): string
    {
        return $this->team->name;
    }

    /**
     * Name the team season by its team's name and the season, such as "FBC Kutná Hora B 2026/2027".
     */
    public function displayNameWithSeason(): string
    {
        return "{$this->displayName()} {$this->season->name}";
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
            'auto_import_enabled' => 'boolean',
        ];
    }
}
