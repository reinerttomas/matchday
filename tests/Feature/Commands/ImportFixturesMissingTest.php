<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Revision;
use App\Models\TeamSeason;
use Tests\Support\Ceskyflorbal;

test('counts a fixture missing from the fixture list once without changing it', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureOfAnotherTeamSeason = Fixture::factory()->for(TeamSeason::factory()->for($teamSeason->season))->create();

    Ceskyflorbal::importFixtureListPagesInTurn($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshotWithout(1306757));

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture)
        ->missing_count->toBe(1)
        ->status->toBe(FixtureStatus::Scheduled)
        ->date->toDateString()->toBe('2026-10-17')
        ->time->toBe('15:00:00');
    expect($fixture->sequence)->toBe(0);
    expect($teamSeason->fixtures()->where('missing_count', '>', 0)->count())->toBe(1)
        ->and($fixtureOfAnotherTeamSeason->refresh()->missing_count)->toBe(0)
        ->and(Revision::query()->count())->toBe(0);
});

test('cancels a fixture missing from the fixture list in two consecutive imports', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithoutFixture = Ceskyflorbal::fixtureListSnapshotWithout(1306757);

    [, , $import] = Ceskyflorbal::importFixtureListPagesInTurn($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $fixtureListWithoutFixture, $fixtureListWithoutFixture);

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture)
        ->status->toBe(FixtureStatus::Cancelled)
        ->missing_count->toBe(2);
    expect($fixture->sequence)->toBe(1);
    expect(Revision::query()->sole())
        ->import_id->toBe($import->id)
        ->fixture_id->toBe($fixture->id)
        ->field->toBe(RevisionField::Status)
        ->old_value->toBe('scheduled')
        ->new_value->toBe('cancelled');
});

test('does not cancel a fixture missing from two imports that were not consecutive', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithoutFixture = Ceskyflorbal::fixtureListSnapshotWithout(1306757);

    Ceskyflorbal::importFixtureListPagesInTurn(
        $teamSeason,
        Ceskyflorbal::fixtureListSnapshot(),
        $fixtureListWithoutFixture,
        Ceskyflorbal::fixtureListSnapshot(),
        $fixtureListWithoutFixture,
    );

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture)
        ->status->toBe(FixtureStatus::Scheduled)
        ->missing_count->toBe(1);
    expect($fixture->sequence)->toBe(0);
    expect(Revision::query()->count())->toBe(0);
});

test('leaves a cancelled fixture unchanged while it stays missing from the fixture list', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithoutFixture = Ceskyflorbal::fixtureListSnapshotWithout(1306757);

    Ceskyflorbal::importFixtureListPagesInTurn(
        $teamSeason,
        Ceskyflorbal::fixtureListSnapshot(),
        $fixtureListWithoutFixture,
        $fixtureListWithoutFixture,
        $fixtureListWithoutFixture,
    );

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture)
        ->status->toBe(FixtureStatus::Cancelled)
        ->missing_count->toBe(2);
    expect($fixture->sequence)->toBe(1);
    expect(Revision::query()->count())->toBe(1);
});

test('takes the status from the fixture list again when a cancelled fixture reappears', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithoutFixture = Ceskyflorbal::fixtureListSnapshotWithout(1306757);

    [, , $cancellingImport, $reappearingImport] = Ceskyflorbal::importFixtureListPagesInTurn(
        $teamSeason,
        Ceskyflorbal::fixtureListSnapshot(),
        $fixtureListWithoutFixture,
        $fixtureListWithoutFixture,
        Ceskyflorbal::fixtureListSnapshot(),
    );

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture)
        ->status->toBe(FixtureStatus::Scheduled)
        ->missing_count->toBe(0);
    expect($fixture->sequence)->toBe(2);
    expect(Revision::query()->orderBy('id')->get(['import_id', 'fixture_id', 'field', 'old_value', 'new_value'])->toArray())->toBe([
        ['import_id' => $cancellingImport->id, 'fixture_id' => $fixture->id, 'field' => 'status', 'old_value' => 'scheduled', 'new_value' => 'cancelled'],
        ['import_id' => $reappearingImport->id, 'fixture_id' => $fixture->id, 'field' => 'status', 'old_value' => 'cancelled', 'new_value' => 'scheduled'],
    ]);
});
