<?php

declare(strict_types=1);

use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\travelTo;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    travelTo('2026-10-06 12:00:00');
});

/**
 * The team season the Změny page shows by default, the only team season in the current season, already through its initial import.
 */
function teamSeasonToMarkSummariesOf(): TeamSeason
{
    $teamSeason = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora B']))->for(Season::factory()->current()->state(['name' => '2026/2027']))->create();
    Import::factory()->for($teamSeason)->create(['started_at' => '2026-09-01 06:00:00']);

    return $teamSeason;
}

/**
 * An import of the team season that recorded a revision, so its change summary waits to be sent.
 */
function importToAnnounce(TeamSeason $teamSeason): Import
{
    $import = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-05 06:00:00']);
    Revision::factory()->for($import)->create();

    return $import;
}

test('marks an import\'s change summary as sent and returns to the page with one summary fewer to send', function () {
    $teamSeason = teamSeasonToMarkSummariesOf();
    $import = importToAnnounce($teamSeason);
    Revision::factory()->for(Import::factory()->for($teamSeason)->state(['started_at' => '2026-10-04 06:00:00']))->create();

    $response = $this->from('/changes')->post("/changes/{$import->id}/sent");

    $response->assertRedirect('/changes')
        ->assertInertiaFlash('toast.message', 'Souhrn změn je označený jako odeslaný.');
    expect($import->fresh()->notified_at->toDateTimeString())->toBe('2026-10-06 12:00:00');
    $this->get('/changes?filter=all')->assertInertia(fn (Assert $page) => $page
        ->where('unsentChangeSummaryCount', 1)
        ->where('revisionHistory.imports.0.notified', 'Odesláno týmu 6. 10. 2026 v 14:00')
        ->etc());
});

test('returns to the imports to send without the one just marked as sent', function () {
    $teamSeason = teamSeasonToMarkSummariesOf();
    $import = importToAnnounce($teamSeason);
    $otherImport = Import::factory()->for($teamSeason)->create(['started_at' => '2026-10-04 06:00:00']);
    Revision::factory()->for($otherImport)->create();

    $this->from('/changes?filter=unsent')->post("/changes/{$import->id}/sent")->assertRedirect('/changes?filter=unsent');

    expect(array_column($this->get('/changes?filter=unsent')->inertiaProps('revisionHistory.imports'), 'id'))->toBe([$otherImport->id]);
});

test('keeps when the team was told about an import already marked as sent', function () {
    $import = importToAnnounce(teamSeasonToMarkSummariesOf());
    $import->update(['notified_at' => '2026-10-05 06:15:00']);

    $this->from('/changes')->post("/changes/{$import->id}/sent")->assertRedirect('/changes');

    expect($import->fresh()->notified_at->toDateTimeString())->toBe('2026-10-05 06:15:00');
});

test('marks an import of a team season other than the selected one, as from a tab opened before switching', function () {
    $teamSeason = teamSeasonToMarkSummariesOf();
    $otherTeamSeason = TeamSeason::factory()->for(Team::factory()->state(['name' => 'FBC Kutná Hora C']))->for($teamSeason->season)->create();
    Import::factory()->for($otherTeamSeason)->create(['started_at' => '2026-09-01 06:00:00']);
    $import = importToAnnounce($otherTeamSeason);

    $this->from('/changes')->post("/changes/{$import->id}/sent")->assertRedirect('/changes');

    expect($import->fresh()->notified_at)->not->toBeNull();
});

test('marks nothing for an import that is not announced to the team', function (Closure $makeImport) {
    $import = $makeImport(teamSeasonToMarkSummariesOf());

    $this->from('/changes')->post("/changes/{$import->id}/sent")->assertNotFound();

    expect($import->fresh()->notified_at)->toBeNull();
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
    $import = importToAnnounce(teamSeasonToMarkSummariesOf());

    $this->post("/changes/{$import->id}/sent")->assertRedirect('/login');

    expect($import->fresh()->notified_at)->toBeNull();
});
