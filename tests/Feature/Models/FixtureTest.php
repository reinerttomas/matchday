<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\TeamSeason;
use App\Models\Venue;
use Illuminate\Database\UniqueConstraintViolationException;

test('fixture belongs to a team season and a venue', function () {
    $teamSeason = TeamSeason::factory()->create();
    $venue = Venue::factory()->create();

    $fixture = Fixture::factory()->for($teamSeason)->for($venue)->create();

    expect($fixture->teamSeason->is($teamSeason))->toBeTrue()
        ->and($fixture->venue?->is($venue))->toBeTrue()
        ->and($teamSeason->fixtures->sole()->is($fixture))->toBeTrue()
        ->and($venue->fixtures->sole()->is($fixture))->toBeTrue();
});

test('fixture venue is optional', function () {
    $fixture = Fixture::factory()->create(['venue_id' => null]);

    expect($fixture->fresh()?->venue)->toBeNull();
});

test('federation match id must be unique within a team season', function () {
    $teamSeason = TeamSeason::factory()->create();
    Fixture::factory()->for($teamSeason)->create(['external_id' => 1395870]);

    expect(fn () => Fixture::factory()->for($teamSeason)->create(['external_id' => 1395870]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('same federation match id can belong to two team seasons', function () {
    Fixture::factory()->create(['external_id' => 1395870]);
    Fixture::factory()->create(['external_id' => 1395870]);

    expect(Fixture::query()->where('external_id', 1395870)->count())->toBe(2);
});
