<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
use App\Models\TeamSeason;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

const FIXTURE_LIST_URL = 'https://www.ceskyflorbal.cz/team/detail/matches/45019';

/**
 * FBC Kutná Hora B in 2026/27, whose live fixture list page was saved as the snapshot.
 */
function kutnaHoraTeamSeason(): TeamSeason
{
    return TeamSeason::factory()
        ->for(Season::factory()->current()->state(['name' => '2026/27']))
        ->notImported()
        ->create([
            'external_id' => 45019,
            'source_url' => FIXTURE_LIST_URL,
        ]);
}

function fixtureListSnapshot(): string
{
    return (string) file_get_contents(base_path('tests/Fixtures/ceskyflorbal/team-matches-45019.html'));
}

test('imports the fixture list of a team season', function () {
    travelTo('2026-10-05 08:00:00');
    $teamSeason = kutnaHoraTeamSeason();
    Http::fake([FIXTURE_LIST_URL => Http::response(fixtureListSnapshot())]);

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])
        ->expectsOutputToContain('Import finished as ok, 24 fixtures found.')
        ->assertSuccessful();

    expect($teamSeason->refresh())
        ->name->toBe('FBC Kutná Hora B')
        ->competition_name->toBe('PH a SČ liga mužů');

    expect(Import::query()->sole())
        ->team_season_id->toBe($teamSeason->id)
        ->trigger->toBe(ImportTrigger::Schedule)
        ->status->toBe(ImportStatus::Ok)
        ->started_at->toDateTimeString()->toBe('2026-10-05 08:00:00')
        ->finished_at->toDateTimeString()->toBe('2026-10-05 08:00:00')
        ->fixtures_found->toBe(24)
        ->error->toBeNull();

    expect($teamSeason->fixtures()->count())->toBe(24);

    expect(Fixture::query()->where('external_id', 1306729)->sole())
        ->round->toBe(1)
        ->date->toDateString()->toBe('2026-09-19')
        ->time->toBeNull()
        ->is_home->toBeFalse()
        ->opponent_name->toBe('ACEMA Sparta Praha C')
        ->status->toBe(FixtureStatus::Finished)
        ->home_score->toBe(3)
        ->away_score->toBe(6)
        ->is_rescheduled->toBeFalse()
        ->venue_id->toBeNull();

    expect(Fixture::query()->where('external_id', 1306733)->sole())
        ->is_home->toBeTrue()
        ->opponent_name->toBe('Lhokamo Praha')
        ->status->toBe(FixtureStatus::Finished)
        ->home_score->toBe(4)
        ->away_score->toBe(2);

    expect(Fixture::query()->where('external_id', 1306757)->sole())
        ->round->toBe(5)
        ->date->toDateString()->toBe('2026-10-17')
        ->time->toBe('15:00:00')
        ->is_home->toBeFalse()
        ->opponent_name->toBe('Tatran Střešovice C')
        ->status->toBe(FixtureStatus::Scheduled)
        ->home_score->toBeNull()
        ->away_score->toBeNull()
        ->is_rescheduled->toBeFalse();

    expect(Fixture::query()->where('external_id', 1306754)->sole())
        ->round->toBe(5)
        ->date->toDateString()->toBe('2026-11-25')
        ->time->toBeNull()
        ->is_home->toBeTrue()
        ->opponent_name->toBe('Las Plantas')
        ->status->toBe(FixtureStatus::Scheduled)
        ->is_rescheduled->toBeTrue();

    Http::assertSent(fn (Request $request): bool => $request->url() === FIXTURE_LIST_URL
        && str_starts_with($request->header('User-Agent')[0], 'Mozilla/5.0'));
});

test('infers the year of a fixture date from the season, not from today', function () {
    travelTo('2027-03-01 12:00:00');
    $teamSeason = kutnaHoraTeamSeason();
    Http::fake([FIXTURE_LIST_URL => Http::response(fixtureListSnapshot())]);

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Fixture::query()->where('external_id', 1306808)->sole()->date->toDateString())->toBe('2026-12-13')
        ->and(Fixture::query()->where('external_id', 1306834)->sole()->date->toDateString())->toBe('2027-01-31')
        ->and(Fixture::query()->where('external_id', 1306878)->sole()->date->toDateString())->toBe('2027-04-04');
});

test('records the import as running while the fixture list downloads', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $statusWhileDownloading = null;
    Http::fake([FIXTURE_LIST_URL => function () use (&$statusWhileDownloading) {
        $statusWhileDownloading = Import::query()->sole()->status;

        return Http::response(fixtureListSnapshot());
    }]);

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect($statusWhileDownloading)->toBe(ImportStatus::Running);
});

test('updates a stored fixture instead of adding it again', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $fixture = Fixture::factory()->for($teamSeason)->create([
        'external_id' => 1306729,
        'date' => '2026-09-19',
        'time' => '10:00:00',
        'venue_id' => null,
    ]);
    Http::fake([FIXTURE_LIST_URL => Http::response(fixtureListSnapshot())]);

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect($teamSeason->fixtures()->count())->toBe(24);

    // A finished row shows the score where the start time was, so the stored time stays.
    expect($fixture->refresh())
        ->status->toBe(FixtureStatus::Finished)
        ->home_score->toBe(3)
        ->away_score->toBe(6)
        ->time->toBe('10:00:00');
});

test('keeps the stored status and logs it when a row shows an unknown status', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $postponedFixture = Fixture::factory()->for($teamSeason)->postponed()->create(['external_id' => 1306757]);
    $snapshot = Str::of(fixtureListSnapshot())
        ->replace('<p class="Match-place">Unihoc Aréna Praha</p>', '<p class="Match-status">kontumace</p>')
        ->replaceFirst('<p class="Match-place">SH Stochov</p>', '<p class="Match-status">kontumace</p>')
        ->toString();
    Http::fake([FIXTURE_LIST_URL => Http::response($snapshot)]);
    Log::spy();

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect($postponedFixture->refresh()->status)->toBe(FixtureStatus::Postponed)
        ->and(Fixture::query()->where('external_id', 1306772)->sole()->status)->toBe(FixtureStatus::Scheduled);

    Log::shouldHaveReceived('warning')
        ->with('Unknown fixture status on the fixture list page.', [
            'team_season_id' => $teamSeason->id,
            'external_id' => 1306757,
            'status' => 'kontumace',
        ])
        ->once();
});

test('fails when the team season does not exist', function () {
    artisan('fixtures:import', ['teamSeason' => 999])
        ->expectsOutputToContain('Team season 999 not found.')
        ->assertFailed();

    expect(Import::query()->count())->toBe(0);
});

test('keeps the stored status and logs it when a row shows neither a venue nor a status', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $postponedFixture = Fixture::factory()->for($teamSeason)->postponed()->create(['external_id' => 1306757]);
    $snapshot = str_replace('<p class="Match-place">Unihoc Aréna Praha</p>', '', fixtureListSnapshot());
    Http::fake([FIXTURE_LIST_URL => Http::response($snapshot)]);
    Log::spy();

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect($postponedFixture->refresh()->status)->toBe(FixtureStatus::Postponed);

    Log::shouldHaveReceived('warning')
        ->with('Unknown fixture status on the fixture list page.', [
            'team_season_id' => $teamSeason->id,
            'external_id' => 1306757,
            'status' => null,
        ])
        ->once();
});

/**
 * Run the initial import of the team season and then a second import, returning the second one.
 */
function importTwice(TeamSeason $teamSeason, string $initialSnapshot, string $nextSnapshot): Import
{
    Http::fake([FIXTURE_LIST_URL => Http::sequence([
        Http::response($initialSnapshot),
        Http::response($nextSnapshot),
    ])]);

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();
    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    return Import::query()->latest('id')->firstOrFail();
}

/**
 * The fixture list snapshot without the row of one fixture.
 */
function fixtureListSnapshotWithout(int $externalId): string
{
    $rows = explode('<div class="Match">', fixtureListSnapshot());

    return implode('<div class="Match">', array_filter(
        $rows,
        fn (string $row): bool => ! str_contains($row, "/match/detail/default/{$externalId}\""),
    ));
}

test('stores the fixtures of the initial import without revisions', function () {
    $teamSeason = kutnaHoraTeamSeason();
    Http::fake([FIXTURE_LIST_URL => Http::response(fixtureListSnapshot())]);

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Revision::query()->count())->toBe(0)
        ->and($teamSeason->fixtures()->where('sequence', '>', 0)->count())->toBe(0);
});

test('treats the first ok import as the initial import even after a failed one', function () {
    $teamSeason = kutnaHoraTeamSeason();
    Import::factory()->for($teamSeason)->error()->create();
    Http::fake([FIXTURE_LIST_URL => Http::response(fixtureListSnapshot())]);

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Revision::query()->count())->toBe(0);
});

test('records a changed start time', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<a href="/match/detail/default/1306757">15:00</a>',
        '<a href="/match/detail/default/1306757">16:30</a>',
        fixtureListSnapshot(),
    );

    $import = importTwice($teamSeason, fixtureListSnapshot(), $snapshot);

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture->time)->toBe('16:30:00')
        ->and($fixture->sequence)->toBe(1);
    expect(Revision::query()->sole())
        ->import_id->toBe($import->id)
        ->fixture_id->toBe($fixture->id)
        ->field->toBe(RevisionField::Time)
        ->old_value->toBe('15:00')
        ->new_value->toBe('16:30');
});

test('records a start time set for a fixture with a TBD time', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<a href="/match/detail/default/1306783">00:00</a>',
        '<a href="/match/detail/default/1306783">18:00</a>',
        fixtureListSnapshot(),
    );

    importTwice($teamSeason, fixtureListSnapshot(), $snapshot);

    expect(Revision::query()->sole())
        ->field->toBe(RevisionField::Time)
        ->old_value->toBeNull()
        ->new_value->toBe('18:00');
});

test('records a finished fixture with its score once in the fixture sequence', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $snapshot = Str::of(fixtureListSnapshot())
        ->replaceMatches(
            '#<time datetime="" class="Match-startTime">\s*<a href="/match/detail/default/1306757">15:00</a>\s*</time>#',
            '<span class="Match-score"><a href="/match/detail/default/1306757">5:3</a></span>',
        )
        ->replace('<p class="Match-place">Unihoc Aréna Praha</p>', '<p class="Match-status">odehráno</p>')
        ->toString();

    importTwice($teamSeason, fixtureListSnapshot(), $snapshot);

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture->time)->toBe('15:00:00')
        ->and($fixture->sequence)->toBe(1);
    expect($fixture->revisions()->orderBy('id')->get(['field', 'old_value', 'new_value'])->toArray())->toBe([
        ['field' => 'status', 'old_value' => 'scheduled', 'new_value' => 'finished'],
        ['field' => 'home_score', 'old_value' => null, 'new_value' => '5'],
        ['field' => 'away_score', 'old_value' => null, 'new_value' => '3'],
    ]);
    expect(Revision::query()->count())->toBe(3);
});

test('records a fixture moved to a new date with the warning icon', function () {
    $teamSeason = kutnaHoraTeamSeason();
    $snapshot = Str::of(fixtureListSnapshot())
        ->replace('SO, 17. 10.', 'SO, 24. 10.')
        ->replace(
            '<p class="Match-place">Unihoc Aréna Praha</p>',
            '<p class="Match-place">Unihoc Aréna Praha</p><span aria-label="odložené utkání 17.10.2026" class="Tooltip Tooltip--warning u-mr-0 Tooltip--container"></span>',
        )
        ->toString();

    importTwice($teamSeason, fixtureListSnapshot(), $snapshot);

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture->status)->toBe(FixtureStatus::Scheduled)
        ->and($fixture->sequence)->toBe(1);
    expect($fixture->revisions()->orderBy('id')->get(['field', 'old_value', 'new_value'])->toArray())->toBe([
        ['field' => 'date', 'old_value' => '2026-10-17', 'new_value' => '2026-10-24'],
        ['field' => 'is_rescheduled', 'old_value' => '0', 'new_value' => '1'],
    ]);
    expect(Revision::query()->count())->toBe(2);
});

test('records a fixture that appears after the initial import as added', function () {
    $teamSeason = kutnaHoraTeamSeason();

    $import = importTwice($teamSeason, fixtureListSnapshotWithout(1306796), fixtureListSnapshot());

    $fixture = Fixture::query()->where('external_id', 1306796)->sole();
    expect($fixture->sequence)->toBe(0);
    expect(Revision::query()->sole())
        ->import_id->toBe($import->id)
        ->fixture_id->toBe($fixture->id)
        ->field->toBeNull()
        ->old_value->toBeNull()
        ->new_value->toBeNull();
});

test('records nothing when the fixture list did not change', function () {
    $teamSeason = kutnaHoraTeamSeason();

    importTwice($teamSeason, fixtureListSnapshot(), fixtureListSnapshot());

    expect(Revision::query()->count())->toBe(0)
        ->and($teamSeason->fixtures()->where('sequence', '>', 0)->count())->toBe(0);
});

test('keeps the fixtures unchanged when recording a revision fails', function () {
    $teamSeason = kutnaHoraTeamSeason();
    Http::fake([FIXTURE_LIST_URL => Http::sequence([
        Http::response(fixtureListSnapshot()),
        Http::response(str_replace(
            '<a href="/match/detail/default/1306757">15:00</a>',
            '<a href="/match/detail/default/1306757">16:30</a>',
            fixtureListSnapshot(),
        )),
    ])]);
    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();
    Revision::creating(fn () => throw new RuntimeException('Writing the revision failed.'));

    expect(fn () => artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->run())
        ->toThrow(RuntimeException::class, 'Writing the revision failed.');

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture->time)->toBe('15:00:00')
        ->and($fixture->sequence)->toBe(0);
});
