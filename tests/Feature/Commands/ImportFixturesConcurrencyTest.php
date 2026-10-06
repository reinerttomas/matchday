<?php

declare(strict_types=1);

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travelTo;

test('skips the import while another import of the team season is running', function () {
    travelTo('2026-10-05 08:00:00');
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Import::factory()->for($teamSeason)->running()->create(['started_at' => '2026-10-05 07:46:00']);
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])
        ->expectsOutputToContain('Import skipped, another import of the team season is running.')
        ->assertSuccessful();

    expect(Import::query()->sole()->status)->toBe(ImportStatus::Running)
        ->and($teamSeason->fixtures()->count())->toBe(0);
    Http::assertNothingSent();
});

test('ends a running import older than 15 minutes as error without emailing and imports the team season', function () {
    travelTo('2026-10-05 08:00:00');
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    User::factory()->create();
    $deadImport = Import::factory()->for($teamSeason)->running()->create(['started_at' => '2026-10-05 07:44:00']);
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));
    Mail::fake();
    Log::spy();

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])
        ->expectsOutputToContain('Import finished as ok, 24 fixtures found.')
        ->assertSuccessful();

    expect($deadImport->refresh())
        ->status->toBe(ImportStatus::Error)
        ->error->toBe('Import nebyl dokončen.')
        ->finished_at->toDateTimeString()->toBe('2026-10-05 08:00:00');
    expect($teamSeason->imports()->latest('id')->first()->status)->toBe(ImportStatus::Ok);
    Log::shouldHaveReceived('error')
        ->with('A running import was never finished, so it ends as error.', [
            'import_id' => $deadImport->id,
            'team_season_id' => $teamSeason->id,
        ])
        ->once();
    Mail::assertNothingOutgoing();
});
