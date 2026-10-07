<?php

declare(strict_types=1);

use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Models\Import;
use App\Models\Season;
use App\Models\TeamSeason;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\artisan;

test('imports each team season of the current season with auto import enabled as a scheduled import', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $otherTeamSeason = TeamSeason::factory()->for($teamSeason->season)->create();
    TeamSeason::factory()->for($teamSeason->season)->autoImportDisabled()->create();
    TeamSeason::factory()->for(Season::factory()->state(['name' => '2025/2026']))->create();
    Http::fake([$otherTeamSeason->source_url => Http::response(Ceskyflorbal::fixtureListSnapshot())]);
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import')
        ->expectsOutputToContain("Team season {$teamSeason->id}: Import finished as ok, 24 fixtures found.")
        ->expectsOutputToContain('2 team seasons: 2 ok, 0 failed, 0 skipped.')
        ->assertSuccessful();

    expect(Import::query()->orderBy('id')->get(['team_season_id', 'trigger', 'status'])->toArray())->toBe([
        ['team_season_id' => $teamSeason->id, 'trigger' => ImportTrigger::Schedule->value, 'status' => ImportStatus::Ok->value],
        ['team_season_id' => $otherTeamSeason->id, 'trigger' => ImportTrigger::Schedule->value, 'status' => ImportStatus::Ok->value],
    ]);
});

test('imports the remaining team seasons after one import throws', function () {
    Exceptions::fake();
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $otherTeamSeason = TeamSeason::factory()->for($teamSeason->season)->create();
    Http::fake([$otherTeamSeason->source_url => Http::response(Ceskyflorbal::fixtureListSnapshot())]);
    Ceskyflorbal::fake(fn () => throw new RuntimeException('Reading the response failed.'));

    artisan('fixtures:import')
        ->expectsOutputToContain("Team season {$teamSeason->id}: Import failed: Reading the response failed.")
        ->expectsOutputToContain('2 team seasons: 1 ok, 1 failed, 0 skipped.')
        ->assertFailed();

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Reading the response failed.');
    expect($teamSeason->imports()->sole()->status)->toBe(ImportStatus::Error)
        ->and($otherTeamSeason->imports()->sole()->status)->toBe(ImportStatus::Ok);
});

test('runs the import command every four hours without overlapping into the next run', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn (Event $event): bool => str_ends_with((string) $event->command, 'fixtures:import'));

    expect($events)->toHaveCount(1)
        ->and($events->sole())
        ->expression->toBe('0 */4 * * *')
        ->withoutOverlapping->toBeTrue()
        ->expiresAt->toBe(230);
});
