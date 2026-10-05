<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Enums\RevisionField;
use App\Mail\ImportFailed;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\TeamSeason;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

/**
 * The fixture list snapshot after fixture 1306757 at Unihoc Aréna Praha was played and won 5:3 by the home team.
 */
function fixtureListSnapshotWithFinishedFixture(): string
{
    return Str::of(Ceskyflorbal::fixtureListSnapshot())
        ->replaceMatches(
            '#<time datetime="" class="Match-startTime">\s*<a href="/match/detail/default/1306757">15:00</a>\s*</time>#',
            '<span class="Match-score"><a href="/match/detail/default/1306757">5:3</a></span>',
        )
        ->replace('<p class="Match-place">Unihoc Aréna Praha</p>', '<p class="Match-status">odehráno</p>')
        ->toString();
}

test('imports the fixture list of a team season', function () {
    travelTo('2026-10-05 08:00:00');
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

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

    Http::assertSent(fn (Request $request): bool => $request->url() === Ceskyflorbal::FIXTURE_LIST_URL
        && str_starts_with($request->header('User-Agent')[0], 'Mozilla/5.0'));
});

test('infers the year of a fixture date from the season, not from today', function () {
    travelTo('2027-03-01 12:00:00');
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Fixture::query()->where('external_id', 1306808)->sole()->date->toDateString())->toBe('2026-12-13')
        ->and(Fixture::query()->where('external_id', 1306834)->sole()->date->toDateString())->toBe('2027-01-31')
        ->and(Fixture::query()->where('external_id', 1306878)->sole()->date->toDateString())->toBe('2027-04-04');
});

test('records the import as running while the fixture list downloads', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $statusWhileDownloading = null;
    Ceskyflorbal::fake(function () use (&$statusWhileDownloading) {
        $statusWhileDownloading = Import::query()->sole()->status;

        return Http::response(Ceskyflorbal::fixtureListSnapshot());
    });

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect($statusWhileDownloading)->toBe(ImportStatus::Running);
});

test('updates a stored fixture instead of adding it again', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixture = Fixture::factory()->for($teamSeason)->create([
        'external_id' => 1306729,
        'date' => '2026-09-19',
        'time' => '10:00:00',
        'venue_id' => null,
    ]);
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

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
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $postponedFixture = Fixture::factory()->for($teamSeason)->postponed()->create(['external_id' => 1306757]);
    $snapshot = Str::of(Ceskyflorbal::fixtureListSnapshot())
        ->replace('<p class="Match-place">Unihoc Aréna Praha</p>', '<p class="Match-status">kontumace</p>')
        ->replaceFirst('<p class="Match-place">SH Stochov</p>', '<p class="Match-status">kontumace</p>')
        ->toString();
    Ceskyflorbal::fake(Http::response($snapshot));
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
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $postponedFixture = Fixture::factory()->for($teamSeason)->postponed()->create(['external_id' => 1306757]);
    $snapshot = str_replace('<p class="Match-place">Unihoc Aréna Praha</p>', '', Ceskyflorbal::fixtureListSnapshot());
    Ceskyflorbal::fake(Http::response($snapshot));
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
 *
 * @param  array<string, mixed>  $matchDetailPages
 */
function importTwice(TeamSeason $teamSeason, string $initialSnapshot, string $nextSnapshot, array $matchDetailPages = []): Import
{
    Ceskyflorbal::fake(Http::sequence([
        Http::response($initialSnapshot),
        Http::response($nextSnapshot),
    ]), $matchDetailPages);

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();
    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    return Import::query()->latest('id')->firstOrFail();
}

/**
 * The fixture list snapshot without the row of one fixture.
 */
function fixtureListSnapshotWithout(int $externalId): string
{
    $rows = explode('<div class="Match">', Ceskyflorbal::fixtureListSnapshot());

    return implode('<div class="Match">', array_filter(
        $rows,
        fn (string $row): bool => ! str_contains($row, "/match/detail/default/{$externalId}\""),
    ));
}

test('stores the fixtures of the initial import without revisions', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Revision::query()->count())->toBe(0)
        ->and($teamSeason->fixtures()->where('sequence', '>', 0)->count())->toBe(0);
});

test('treats the first ok import as the initial import even after a failed one', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Import::factory()->for($teamSeason)->error()->create();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Revision::query()->count())->toBe(0);
});

test('records a changed start time', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<a href="/match/detail/default/1306757">15:00</a>',
        '<a href="/match/detail/default/1306757">16:30</a>',
        Ceskyflorbal::fixtureListSnapshot(),
    );

    $import = importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

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
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<a href="/match/detail/default/1306783">00:00</a>',
        '<a href="/match/detail/default/1306783">18:00</a>',
        Ceskyflorbal::fixtureListSnapshot(),
    );

    importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

    expect(Revision::query()->sole())
        ->field->toBe(RevisionField::Time)
        ->old_value->toBeNull()
        ->new_value->toBe('18:00');
});

test('records a finished fixture with its score once in the fixture sequence', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), fixtureListSnapshotWithFinishedFixture());

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
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = Str::of(Ceskyflorbal::fixtureListSnapshot())
        ->replace('SO, 17. 10.', 'SO, 24. 10.')
        ->replace(
            '<p class="Match-place">Unihoc Aréna Praha</p>',
            '<p class="Match-place">Unihoc Aréna Praha</p><span aria-label="odložené utkání 17.10.2026" class="Tooltip Tooltip--warning u-mr-0 Tooltip--container"></span>',
        )
        ->toString();

    importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

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
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    $import = importTwice($teamSeason, fixtureListSnapshotWithout(1306796), Ceskyflorbal::fixtureListSnapshot());

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
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshot());

    expect(Revision::query()->count())->toBe(0)
        ->and($teamSeason->fixtures()->where('sequence', '>', 0)->count())->toBe(0);
});

test('keeps the fixtures unchanged and ends the import as error when recording a revision fails', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $user = User::factory()->create();
    Mail::fake();
    Ceskyflorbal::fake(Http::sequence([
        Http::response(Ceskyflorbal::fixtureListSnapshot()),
        Http::response(str_replace(
            '<a href="/match/detail/default/1306757">15:00</a>',
            '<a href="/match/detail/default/1306757">16:30</a>',
            Ceskyflorbal::fixtureListSnapshot(),
        )),
    ]));
    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();
    Revision::creating(fn () => throw new RuntimeException('Writing the revision failed.'));

    expect(fn () => artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->run())
        ->toThrow(RuntimeException::class, 'Writing the revision failed.');

    $fixture = Fixture::query()->where('external_id', 1306757)->sole();
    expect($fixture->time)->toBe('15:00:00')
        ->and($fixture->sequence)->toBe(0);
    $import = Import::query()->latest('id')->firstOrFail();
    expect($import)
        ->status->toBe(ImportStatus::Error)
        ->error->toBe('Neočekávaná chyba při importu')
        ->finished_at->not->toBeNull()
        ->fixtures_found->toBeNull();
    Mail::assertQueued(ImportFailed::class, fn (ImportFailed $mail): bool => $mail->hasTo($user->email) && $mail->import->is($import));
});

test('stores the venue of a new fixture with its address from the match detail page', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Fixture::query()->where('external_id', 1306754)->sole()->venue)
        ->external_id->toBe(602)
        ->name->toBe('SH Kutná Hora Klimeška')
        ->address->toBe('Čáslavská 274, Kutná Hora');
    expect(Venue::query()->count())->toBe(10);
    Http::assertSent(fn (Request $request): bool => $request->url() === Ceskyflorbal::MATCH_DETAIL_URL.'1306754'
        && str_starts_with($request->header('User-Agent')[0], 'Mozilla/5.0'));
});

test('downloads no match detail page for a finished row', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Ceskyflorbal::requestedMatchDetailUrls())->not->toContain(Ceskyflorbal::MATCH_DETAIL_URL.'1306729')
        ->and(Fixture::query()->where('external_id', 1306729)->sole()->venue_id)->toBeNull();
});

test('pauses before each match detail page request', function () {
    config(['services.ceskyflorbal.request_pause_milliseconds' => 1500]);
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Ceskyflorbal::requestedMatchDetailUrls())->toHaveCount(20);
    Sleep::assertSequence(array_fill(0, 20, Sleep::for(1500)->milliseconds()));
});

test('does not download a match detail page again for an unchanged venue', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshot());

    expect(Ceskyflorbal::requestedMatchDetailUrls())->toHaveCount(20)
        ->and(Revision::query()->count())->toBe(0);
});

test('records a fixture moved to another venue', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<p class="Match-place">SH Kutná Hora Klimeška</p>',
        '<p class="Match-place">SH Kutná Hora Šipší</p>',
        Ceskyflorbal::fixtureListSnapshot(),
    );

    $import = importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot, [
        Ceskyflorbal::MATCH_DETAIL_URL.'1306754' => Http::sequence([
            Http::response(Ceskyflorbal::matchDetailSnapshot()),
            Http::response(Ceskyflorbal::matchDetailSnapshot('SH Kutná Hora Šipší', 731)),
        ]),
    ]);

    $fixture = Fixture::query()->where('external_id', 1306754)->sole();
    expect($fixture->venue)
        ->external_id->toBe(731)
        ->name->toBe('SH Kutná Hora Šipší');
    expect($fixture->sequence)->toBe(1);
    expect(Revision::query()->sole())
        ->import_id->toBe($import->id)
        ->fixture_id->toBe($fixture->id)
        ->field->toBe(RevisionField::Venue)
        ->old_value->toBe('SH Kutná Hora Klimeška')
        ->new_value->toBe('SH Kutná Hora Šipší');
    expect(Ceskyflorbal::requestedMatchDetailUrls())->toHaveCount(21);
});

test('keeps the venue of a fixture whose row shows it finished', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), fixtureListSnapshotWithFinishedFixture());

    expect(Fixture::query()->where('external_id', 1306757)->sole()->venue->name)->toBe('Unihoc Aréna Praha')
        ->and(Revision::query()->where('field', RevisionField::Venue)->count())->toBe(0)
        ->and(Ceskyflorbal::requestedMatchDetailUrls())->toHaveCount(20);
});

test('renames a stored venue the match detail page shows under another name', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $venue = Venue::factory()->create([
        'external_id' => 602,
        'name' => 'SH Klimeška',
        'address' => 'Čáslavská 274, 284 01 Kutná Hora',
    ]);
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect($venue->refresh())
        ->name->toBe('SH Kutná Hora Klimeška')
        ->address->toBe('Čáslavská 274, 284 01 Kutná Hora');
    expect(Fixture::query()->where('external_id', 1306754)->sole()->venue_id)->toBe($venue->id);
});

test('fills in the address of a stored venue that has none', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $venue = Venue::factory()->withoutAddress()->create([
        'external_id' => 602,
        'name' => 'SH Kutná Hora Klimeška',
    ]);
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect($venue->refresh()->address)->toBe('Čáslavská 274, Kutná Hora');
});
