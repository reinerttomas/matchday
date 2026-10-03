<?php

declare(strict_types=1);

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
