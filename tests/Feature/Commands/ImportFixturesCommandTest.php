<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Models\Fixture;
use App\Models\Import;
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
