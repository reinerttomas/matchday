<?php

declare(strict_types=1);

use App\Mail\FixtureListRevised;
use App\Models\Import;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;

test('gives the change summary line by line with a link that opens WhatsApp', function () {
    $import = Import::factory()
        ->for(TeamSeason::factory()
            ->for(Team::factory()->state(['name' => 'FBC Kutná Hora B', 'slug' => 'kutna-hora-b']))
            ->for(Season::factory()->state(['name' => '2026/2027']))
            ->state(['name' => 'FBC Kutná Hora B']))
        ->create();
    $summary = implode("\n", [
        '📅 Změny v rozpisu FBC Kutná Hora B',
        '• NE 15. 11. FBC Kutná Hora B – TBC Engineers Horoměřice: čas doplněn 9:00',
        '',
        'Kalendář: https://matchday.example/t/kutna-hora-b',
    ]);

    $mail = new FixtureListRevised($import, $summary, 'https://wa.me/?text=%F0%9F%93%85');

    $mail->assertHasSubject('Změny v rozpisu: FBC Kutná Hora B 2026/2027');
    $mail->assertSeeInOrderInText([
        'FBC Kutná Hora B 2026/2027',
        '📅 Změny v rozpisu FBC Kutná Hora B',
        '• NE 15. 11. FBC Kutná Hora B – TBC Engineers Horoměřice: čas doplněn 9:00',
        'Kalendář: https://matchday.example/t/kutna-hora-b',
    ]);
    $mail->assertSeeInHtml('📅 Změny v rozpisu FBC Kutná Hora B<br>', escape: false);
    $mail->assertSeeInHtml('href="https://wa.me/?text=%F0%9F%93%85"', escape: false);
});

test('shows Markdown punctuation in the change summary literally', function () {
    $import = Import::factory()->create();
    $summary = "📅 Změny v rozpisu FBC Kutná Hora B\n• NE 15. 11. FBC Kutná Hora B – *Sparta* _B_ [C](x) <b>: čas doplněn 9:00";

    $mail = new FixtureListRevised($import, $summary, 'https://wa.me/?text=%F0%9F%93%85');

    $mail->assertSeeInHtml('• NE 15. 11. FBC Kutná Hora B – *Sparta* _B_ [C](x) &lt;b&gt;: čas doplněn 9:00', escape: false);
    $mail->assertSeeInText('• NE 15. 11. FBC Kutná Hora B – *Sparta* _B_ [C](x) <b>: čas doplněn 9:00');
});
