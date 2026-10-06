<?php

declare(strict_types=1);

use App\Models\Import;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\User;
use App\Notifications\FixtureListRevised;
use Illuminate\Mail\Markdown;
use Illuminate\Testing\Constraints\SeeInOrder;

test('gives the change summary line by line with a link that opens WhatsApp', function () {
    $import = Import::factory()
        ->for(TeamSeason::factory()
            ->for(Team::factory()->state(['slug' => 'kutna-hora-b']))
            ->for(Season::factory()->state(['name' => '2026/27']))
            ->state(['name' => 'FBC Kutná Hora B']))
        ->create();
    $summary = implode("\n", [
        '📅 Změny v rozpisu FBC Kutná Hora B',
        '• NE 15. 11. FBC Kutná Hora B – TBC Engineers Horoměřice: čas doplněn 9:00',
        '',
        'Kalendář: https://matchday.example/t/kutna-hora-b',
    ]);

    $mail = (new FixtureListRevised($import, $summary, 'https://wa.me/?text=%F0%9F%93%85'))->toMail(User::factory()->make());

    expect($mail->subject)->toBe('Změny v rozpisu: FBC Kutná Hora B 2026/27');
    $this->assertThat([
        'FBC Kutná Hora B 2026/27',
        '📅 Změny v rozpisu FBC Kutná Hora B',
        '• NE 15. 11. FBC Kutná Hora B – TBC Engineers Horoměřice: čas doplněn 9:00',
        'Kalendář: https://matchday.example/t/kutna-hora-b',
    ], new SeeInOrder((string) app(Markdown::class)->renderText($mail->markdown, $mail->data())));
    expect((string) $mail->render())
        ->toContain('📅 Změny v rozpisu FBC Kutná Hora B<br>')
        ->toContain('href="https://wa.me/?text=%F0%9F%93%85"');
});

test('shows Markdown punctuation in the change summary literally', function () {
    $import = Import::factory()->create();
    $summary = "📅 Změny v rozpisu FBC Kutná Hora B\n• NE 15. 11. FBC Kutná Hora B – *Sparta* _B_ [C](x) <b>: čas doplněn 9:00";

    $mail = (new FixtureListRevised($import, $summary, 'https://wa.me/?text=%F0%9F%93%85'))->toMail(User::factory()->make());

    expect((string) $mail->render())->toContain('• NE 15. 11. FBC Kutná Hora B – *Sparta* _B_ [C](x) &lt;b&gt;: čas doplněn 9:00');
    expect((string) app(Markdown::class)->renderText($mail->markdown, $mail->data()))
        ->toContain('• NE 15. 11. FBC Kutná Hora B – *Sparta* _B_ [C](x) <b>: čas doplněn 9:00');
});
