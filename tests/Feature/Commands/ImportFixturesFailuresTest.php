<?php

declare(strict_types=1);

use App\Enums\ImportStatus;
use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\TeamSeason;
use App\Models\User;
use App\Models\Venue;
use App\Notifications\ImportFailed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

/**
 * FBC Kutná Hora B after an ok import of the 24 fixtures in the snapshot, with fixture 1306754 stored at SH Kutná Hora Klimeška at 10:00, a time the snapshot shows as TBD.
 */
function importedKutnaHoraTeamSeason(): TeamSeason
{
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $teamSeason->update(['name' => 'FBC Kutná Hora B', 'competition_name' => 'PH a SČ liga mužů']);
    Import::factory()->for($teamSeason)->create(['fixtures_found' => 24]);
    Fixture::factory()
        ->for($teamSeason)
        ->for(Venue::factory()->state(['external_id' => 602, 'name' => 'SH Kutná Hora Klimeška']))
        ->create(['external_id' => 1306754, 'date' => '2026-11-25', 'time' => '10:00:00']);

    return $teamSeason;
}

/**
 * Everything an import may change apart from the import itself.
 *
 * @return array<string, array<int, array<string, mixed>>>
 */
function importableData(): array
{
    return [
        'fixtures' => Fixture::query()->orderBy('id')->get()->toArray(),
        'venues' => Venue::query()->orderBy('id')->get()->toArray(),
        'revisions' => Revision::query()->orderBy('id')->get()->toArray(),
        'team_seasons' => TeamSeason::query()->orderBy('id')->get()->toArray(),
    ];
}

/**
 * The fixture list snapshot cut off after its first rows.
 */
function fixtureListSnapshotWithFirstRows(int $count): string
{
    return implode('<div class="Match">', array_slice(explode('<div class="Match">', Ceskyflorbal::fixtureListSnapshot()), 0, $count + 1));
}

function latestImport(): Import
{
    return Import::query()->latest('id')->firstOrFail();
}

/**
 * Assert that every user, and nobody else, was emailed about the import.
 */
function assertAdministratorsEmailedAbout(Import $import): void
{
    $users = User::query()->get();

    foreach ($users as $user) {
        Notification::assertSentTo($user, ImportFailed::class, fn (ImportFailed $notification): bool => $notification->import->is($import));
    }

    Notification::assertCount($users->count());
}

test('ends the import as error without changing data when ceskyflorbal.cz blocks the download', function () {
    travelTo('2026-10-05 08:00:00');
    $teamSeason = importedKutnaHoraTeamSeason();
    User::factory()->count(2)->create();
    $dataBefore = importableData();
    Ceskyflorbal::fake(Http::response('Forbidden', 403));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])
        ->expectsOutputToContain('Import finished as error: HTTP 403 – požadavek zablokován')
        ->assertFailed();

    expect(latestImport())
        ->status->toBe(ImportStatus::Error)
        ->error->toBe('HTTP 403 – požadavek zablokován')
        ->finished_at->toDateTimeString()->toBe('2026-10-05 08:00:00')
        ->fixtures_found->toBeNull();
    expect(importableData())->toBe($dataBefore);
    assertAdministratorsEmailedAbout(latestImport());
});

test('describes in Czech why ceskyflorbal.cz refused the fixture list', function (int $status, string $reason) {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response('', $status));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertFailed();

    expect(latestImport()->error)->toBe($reason);
})->with([
    'missing page' => [404, 'HTTP 404 – stránka nenalezena'],
    'too many requests' => [429, 'HTTP 429 – příliš mnoho požadavků'],
    'server error' => [503, 'HTTP 503 – chyba serveru ceskyflorbal.cz'],
    'other status' => [418, 'HTTP 418 – neočekávaná odpověď'],
]);

test('ends the import as error without changing data when ceskyflorbal.cz cannot be reached', function () {
    $teamSeason = importedKutnaHoraTeamSeason();
    User::factory()->create();
    $dataBefore = importableData();
    Ceskyflorbal::fake(Http::failedConnection());

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertFailed();

    expect(latestImport())
        ->status->toBe(ImportStatus::Error)
        ->error->toBe('Nepodařilo se spojit s ceskyflorbal.cz')
        ->finished_at->not->toBeNull();
    expect(importableData())->toBe($dataBefore);
    assertAdministratorsEmailedAbout(latestImport());
});

test('ends the import as error without changing data when a fixture row cannot be read', function () {
    $teamSeason = importedKutnaHoraTeamSeason();
    User::factory()->create();
    $dataBefore = importableData();
    Ceskyflorbal::fake(Http::response(str_replace('SO, 17. 10.', 'SO, 17.', Ceskyflorbal::fixtureListSnapshot())));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertFailed();

    expect(latestImport())
        ->status->toBe(ImportStatus::Error)
        ->error->toBe('Stránku s rozpisem zápasů nelze přečíst: Unreadable fixture date [SO, 17.].')
        ->finished_at->not->toBeNull();
    expect(importableData())->toBe($dataBefore);
    assertAdministratorsEmailedAbout(latestImport());
});

test('aborts the import without changing data when the fixture list page shows no fixtures', function () {
    travelTo('2026-10-05 08:00:00');
    $teamSeason = importedKutnaHoraTeamSeason();
    User::factory()->create();
    $dataBefore = importableData();
    Ceskyflorbal::fake(Http::response(fixtureListSnapshotWithFirstRows(0)));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])
        ->expectsOutputToContain('Import finished as aborted: Parser vrátil 0 zápasů (minule 24)')
        ->assertFailed();

    expect(latestImport())
        ->status->toBe(ImportStatus::Aborted)
        ->error->toBe('Parser vrátil 0 zápasů (minule 24)')
        ->finished_at->toDateTimeString()->toBe('2026-10-05 08:00:00')
        ->fixtures_found->toBe(0);
    expect(importableData())->toBe($dataBefore);
    assertAdministratorsEmailedAbout(latestImport());
});

test('aborts the import without changing data when the page shows fewer than half of the fixtures of the last ok import', function (int $rows, string $reason) {
    $teamSeason = importedKutnaHoraTeamSeason();
    User::factory()->create();
    $dataBefore = importableData();
    Ceskyflorbal::fake(Http::response(fixtureListSnapshotWithFirstRows($rows)));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertFailed();

    expect(latestImport())
        ->status->toBe(ImportStatus::Aborted)
        ->error->toBe($reason)
        ->fixtures_found->toBe($rows);
    expect(importableData())->toBe($dataBefore);
    assertAdministratorsEmailedAbout(latestImport());
})->with([
    'one fixture' => [1, 'Parser vrátil 1 zápas (minule 24)'],
    'a few fixtures' => [3, 'Parser vrátil 3 zápasy (minule 24)'],
    'one fixture short of half' => [11, 'Parser vrátil 11 zápasů (minule 24)'],
]);

test('applies a page with half of the fixtures of the last ok import', function () {
    $teamSeason = importedKutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(fixtureListSnapshotWithFirstRows(12)));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(latestImport())
        ->status->toBe(ImportStatus::Ok)
        ->fixtures_found->toBe(12);
    Notification::assertSentTimes(ImportFailed::class, 0);
});

test('aborts the first import of a team season whose page shows no fixtures', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(fixtureListSnapshotWithFirstRows(0)));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertFailed();

    expect(latestImport())
        ->status->toBe(ImportStatus::Aborted)
        ->error->toBe('Parser vrátil 0 zápasů');
});

test('aborts the import without changing data when the page belongs to another season', function () {
    $teamSeason = importedKutnaHoraTeamSeason();
    User::factory()->create();
    $dataBefore = importableData();
    Ceskyflorbal::fake(Http::response(str_replace('PH A SČ LIGA MUŽŮ 2026/2027', 'PH A SČ LIGA MUŽŮ 2025/2026', Ceskyflorbal::fixtureListSnapshot())));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertFailed();

    expect(latestImport())
        ->status->toBe(ImportStatus::Aborted)
        ->error->toBe('Rozpis na stránce je ze sezony 2025/26, ne 2026/27')
        ->fixtures_found->toBe(24);
    expect(importableData())->toBe($dataBefore)
        ->and(Ceskyflorbal::requestedMatchDetailUrls())->toBe([]);
    assertAdministratorsEmailedAbout(latestImport());
});

test('ends the import as error when the team header shows no season', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(str_replace('PH A SČ LIGA MUŽŮ 2026/2027', 'PH A SČ LIGA MUŽŮ', Ceskyflorbal::fixtureListSnapshot())));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertFailed();

    expect(latestImport())
        ->status->toBe(ImportStatus::Error)
        ->error->toBe('Stránku s rozpisem zápasů nelze přečíst: Unreadable season in the team header [PH A SČ LIGA MUŽŮ].');
});

test('applies a fixture with its venue unchanged and logs it when its match detail page fails', function (mixed $matchDetailPage) {
    $teamSeason = importedKutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<p class="Match-place">SH Kutná Hora Klimeška</p>',
        '<p class="Match-place">SH Kutná Hora Šipší</p>',
        Ceskyflorbal::fixtureListSnapshot(),
    );
    Ceskyflorbal::fake(Http::response($snapshot), [Ceskyflorbal::MATCH_DETAIL_URL.'1306754' => $matchDetailPage]);
    Log::spy();

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(latestImport()->status)->toBe(ImportStatus::Ok);
    $fixture = Fixture::query()->where('external_id', 1306754)->sole();
    expect($fixture->venue->name)->toBe('SH Kutná Hora Klimeška')
        ->and($fixture->revisions()->where('field', RevisionField::Venue)->exists())->toBeFalse();
    expect($fixture->revisions()->where('field', RevisionField::Time)->sole())
        ->old_value->toBe('10:00')
        ->new_value->toBeNull();
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $message, array $context): bool => $message === 'The match detail page failed, so the fixture keeps its venue until a later import.'
            && $context['team_season_id'] === $teamSeason->id
            && $context['external_id'] === 1306754)
        ->once();
    Notification::assertSentTimes(ImportFailed::class, 0);
})->with([
    'blocked' => fn () => Http::response('Forbidden', 403),
    'unreachable' => fn () => Http::failedConnection(),
    'unreadable' => fn () => Http::response('<html><body></body></html>'),
]);
