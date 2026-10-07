<?php

declare(strict_types=1);

use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use Illuminate\Database\UniqueConstraintViolationException;

test('team season links a team to a season', function () {
    $team = Team::factory()->create();
    $season = Season::factory()->create();

    $teamSeason = TeamSeason::factory()->for($team)->for($season)->create();

    expect($teamSeason->team->is($team))->toBeTrue()
        ->and($teamSeason->season->is($season))->toBeTrue()
        ->and($team->teamSeasons->sole()->is($teamSeason))->toBeTrue()
        ->and($season->teamSeasons->sole()->is($teamSeason))->toBeTrue();
});

test('federation team id must be unique', function () {
    TeamSeason::factory()->create(['external_id' => 45019]);

    expect(fn () => TeamSeason::factory()->create(['external_id' => 45019]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('team can take part in a season only once', function () {
    $team = Team::factory()->create();
    $season = Season::factory()->create();
    TeamSeason::factory()->for($team)->for($season)->create();

    expect(fn () => TeamSeason::factory()->for($team)->for($season)->create())
        ->toThrow(UniqueConstraintViolationException::class);
});

test('revising imports are those with revisions, without the initial import, also when eager loaded', function () {
    $teamSeason = TeamSeason::factory()->create();
    Import::factory()->for($teamSeason)->error()->create(['started_at' => '2026-09-01 07:00:00']);
    $initial = Import::factory()->for($teamSeason)->create(['started_at' => '2026-09-01 08:00:00']);
    $sameStart = Import::factory()->for($teamSeason)->create(['started_at' => '2026-09-01 08:00:00']);
    $later = Import::factory()->for($teamSeason)->create(['started_at' => '2026-09-08 08:00:00']);
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-09-15 08:00:00']);
    foreach ([$initial, $sameStart, $later] as $import) {
        Revision::factory()->for($import)->create();
    }
    Revision::factory()->for(Import::factory()->create(['started_at' => '2026-09-08 08:00:00']))->create();

    expect($teamSeason->revisingImports()->orderBy('id')->pluck('id')->all())->toBe([$sameStart->id, $later->id])
        ->and(TeamSeason::query()->with('revisingImports')->find($teamSeason->id)->revisingImports->sortBy('id')->pluck('id')->values()->all())->toBe([$sameStart->id, $later->id]);
});
