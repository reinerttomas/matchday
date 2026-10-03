<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\TeamSeason;

test('revision belongs to a fixture and an import', function () {
    $teamSeason = TeamSeason::factory()->create();
    $fixture = Fixture::factory()->for($teamSeason)->create();
    $import = Import::factory()->for($teamSeason)->create();

    $revision = Revision::factory()->for($fixture)->for($import)->create();

    expect($revision->fixture->is($fixture))->toBeTrue()
        ->and($revision->import->is($import))->toBeTrue()
        ->and($fixture->revisions->sole()->is($revision))->toBeTrue()
        ->and($import->revisions->sole()->is($revision))->toBeTrue();
});

test('revision without a field records an added fixture', function () {
    $revision = Revision::factory()->fixtureAdded()->create();

    expect($revision->fresh())
        ->field->toBeNull()
        ->old_value->toBeNull()
        ->new_value->toBeNull();
});
