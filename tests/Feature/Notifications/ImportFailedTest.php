<?php

declare(strict_types=1);

use App\Models\Import;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use App\Notifications\ImportFailed;
use Database\Factories\TeamSeasonFactory;
use Illuminate\Mail\Markdown;
use Illuminate\Testing\Constraints\SeeInOrder;

function kutnaHoraTeamSeasonFactory(): TeamSeasonFactory
{
    return TeamSeason::factory()
        ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
        ->for(Season::factory()->state(['name' => '2026/2027']))
        ->state([
            'name' => 'FBC Kutná Hora B',
            'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
        ]);
}

test('tells why an import ended with an error', function () {
    $import = Import::factory()->for(kutnaHoraTeamSeasonFactory())->error('HTTP 403 – požadavek zablokován')->create();

    $mail = (new ImportFailed($import))->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Import rozpisu selhal: FBC Kutná Hora B 2026/2027');
    $this->assertThat([
        'FBC Kutná Hora B 2026/2027',
        'skončil chybou. Uložený rozpis zápasů zůstal beze změny.',
        'HTTP 403 – požadavek zablokován',
    ], new SeeInOrder((string) app(Markdown::class)->renderText($mail->markdown, $mail->data())));
    expect((string) $mail->render())->toContain('https://www.ceskyflorbal.cz/team/detail/matches/45019');
});

test('tells why an import was aborted', function () {
    $import = Import::factory()->for(kutnaHoraTeamSeasonFactory())->aborted('Parser vrátil 0 zápasů (minule 24)')->create();

    $mail = (new ImportFailed($import))->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Import rozpisu přerušen: FBC Kutná Hora B 2026/2027');
    $this->assertThat([
        'FBC Kutná Hora B 2026/2027',
        'byl přerušen. Uložený rozpis zápasů zůstal beze změny.',
        'Parser vrátil 0 zápasů (minule 24)',
    ], new SeeInOrder((string) app(Markdown::class)->renderText($mail->markdown, $mail->data())));
});

test('names the team season by its team\'s name rather than the name its import read', function () {
    $import = Import::factory()->for(kutnaHoraTeamSeasonFactory()->state(['name' => 'Florbal Kutná Hora B']))->error()->create();

    $mail = (new ImportFailed($import))->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Import rozpisu selhal: FBC Kutná Hora B 2026/2027');
});
