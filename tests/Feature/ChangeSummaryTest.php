<?php

declare(strict_types=1);

use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    travelTo('2026-10-06 12:00:00');
});

/**
 * The team season the Změny page shows by default: FBC Kutná Hora B, the only team season in the current season, already through its initial import.
 */
function teamSeasonWithChangeSummaries(): TeamSeason
{
    $teamSeason = TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->current()->state(['name' => '2026/2027']))
        ->create(['name' => 'FBC Kutná Hora B']);
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-09-01 06:00:00']);

    return $teamSeason;
}

test('serves the change summary of an import for the WhatsApp dialog', function () {
    $teamSeason = teamSeasonWithChangeSummaries();
    $import = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 06:00:00']);
    $fixture = Fixture::factory()->for($teamSeason)->home()->create(['date' => '2026-10-11', 'time' => '09:00:00', 'opponent_name' => 'Las Plantas']);
    Revision::factory()->for($import)->for($fixture)->fieldChange(RevisionField::Time, null, '09:00')->create();

    $response = $this->getJson("/changes/{$import->id}/summary");

    $response->assertOk()->assertExactJson(['summary' => implode("\n", [
        '📅 Změny v rozpisu FBC Kutná Hora B',
        '• NE 11. 10. FBC Kutná Hora B – Las Plantas: čas doplněn 9:00',
        '',
        'Kalendář: '.route('public-team-page', 'kutna-hora-b'),
    ])]);
});

test('serves no change summary when none of the import\'s revisions is worth telling the team', function () {
    $teamSeason = teamSeasonWithChangeSummaries();
    $import = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 06:00:00']);
    $fixture = Fixture::factory()->for($teamSeason)->create(['is_rescheduled' => false]);
    Revision::factory()->for($import)->for($fixture)->fieldChange(RevisionField::IsRescheduled, '1', '0')->create();

    $this->getJson("/changes/{$import->id}/summary")
        ->assertOk()
        ->assertExactJson(['summary' => null]);
});

test('serves no change summary for an import that is not announced to the team', function (Closure $makeImport) {
    $import = $makeImport(teamSeasonWithChangeSummaries());

    $this->getJson("/changes/{$import->id}/summary")->assertNotFound();
})->with([
    'the initial import' => [function (TeamSeason $teamSeason): Import {
        $initialImport = $teamSeason->initialImport()->sole();
        Revision::factory()->for($initialImport)->create();

        return $initialImport;
    }],
    'an import without revisions' => [fn (TeamSeason $teamSeason): Import => Import::factory()->for($teamSeason)->create()],
]);

test('redirects guests to the login page', function () {
    auth()->logout();
    $import = Import::factory()->for(teamSeasonWithChangeSummaries())->create(['started_at' => '2026-10-05 06:00:00']);
    Revision::factory()->for($import)->create();

    $this->get("/changes/{$import->id}/summary")->assertRedirect('/login');
});
