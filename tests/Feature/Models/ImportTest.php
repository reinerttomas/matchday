<?php

declare(strict_types=1);

use App\Models\Import;
use App\Models\TeamSeason;

test('import belongs to a team season', function () {
    $teamSeason = TeamSeason::factory()->create();

    $import = Import::factory()->for($teamSeason)->create();

    expect($import->teamSeason->is($teamSeason))->toBeTrue()
        ->and($teamSeason->imports->sole()->is($import))->toBeTrue();
});
