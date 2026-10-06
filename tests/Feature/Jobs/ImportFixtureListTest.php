<?php

declare(strict_types=1);

use App\Enums\ImportStatus;
use App\Enums\ImportTrigger;
use App\Jobs\ImportFixtureList;
use App\Models\Import;
use Illuminate\Support\Facades\Http;
use Tests\Support\Ceskyflorbal;

test('imports the team season as a manual import', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    dispatch(new ImportFixtureList($teamSeason));

    expect(Import::query()->sole())
        ->team_season_id->toBe($teamSeason->id)
        ->trigger->toBe(ImportTrigger::Manual)
        ->status->toBe(ImportStatus::Ok);

    expect($teamSeason->fixtures()->count())->toBe(24);
});
