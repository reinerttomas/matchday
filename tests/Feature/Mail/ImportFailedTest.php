<?php

declare(strict_types=1);

use App\Mail\ImportFailed;
use App\Models\Import;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use Database\Factories\TeamSeasonFactory;

function kutnaHoraTeamSeasonFactory(): TeamSeasonFactory
{
    return TeamSeason::factory()
        ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
        ->for(Season::factory()->state(['name' => '2026/27']))
        ->state([
            'name' => 'FBC Kutná Hora B',
            'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        ]);
}

test('tells why an import ended with an error', function () {
    $import = Import::factory()->for(kutnaHoraTeamSeasonFactory())->error('HTTP 403 – požadavek zablokován')->create();

    $mail = new ImportFailed($import);

    $mail->assertHasSubject('Import rozpisu selhal: FBC Kutná Hora B 2026/27');
    $mail->assertSeeInOrderInText([
        'FBC Kutná Hora B 2026/27',
        'skončil chybou. Uložený rozpis zápasů zůstal beze změny.',
        'HTTP 403 – požadavek zablokován',
    ]);
    $mail->assertSeeInHtml('https://www.ceskyflorbal.cz/team/detail/matches/45019', escape: false);
});

test('tells why an import was aborted', function () {
    $import = Import::factory()->for(kutnaHoraTeamSeasonFactory())->aborted('Parser vrátil 0 zápasů (minule 24)')->create();

    $mail = new ImportFailed($import);

    $mail->assertHasSubject('Import rozpisu přerušen: FBC Kutná Hora B 2026/27');
    $mail->assertSeeInOrderInText([
        'FBC Kutná Hora B 2026/27',
        'byl přerušen. Uložený rozpis zápasů zůstal beze změny.',
        'Parser vrátil 0 zápasů (minule 24)',
    ]);
});

test('names a team season that was never imported by its team slug', function () {
    $import = Import::factory()->for(kutnaHoraTeamSeasonFactory()->notImported())->error()->create();

    $mail = new ImportFailed($import);

    $mail->assertHasSubject('Import rozpisu selhal: kutna-hora-b 2026/27');
});
