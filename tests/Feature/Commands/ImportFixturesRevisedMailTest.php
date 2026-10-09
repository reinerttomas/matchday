<?php

declare(strict_types=1);

use App\Mail\FixtureListRevised;
use App\Models\Import;
use App\Models\User;
use App\Services\ChangeSummaryWriter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\Ceskyflorbal;

use function Pest\Laravel\artisan;

test('emails every user the change summary of an import that recorded revisions', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    User::factory()->count(2)->sequence(['email' => 'petr@example.com'], ['email' => 'jana@example.com'])->create();
    $snapshot = Ceskyflorbal::fixtureListSnapshotWithTime(1306757, '16:30');

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

    $summary = app(ChangeSummaryWriter::class)->write($import);
    expect($summary)->toContain('• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: nový čas 16:30 (původně 15:00)');
    Mail::assertQueued(FixtureListRevised::class, fn (FixtureListRevised $mail): bool => $mail->import->is($import)
        && $mail->summary === $summary
        && $mail->whatsAppUrl === app(ChangeSummaryWriter::class)->whatsAppUrl($summary)
        && collect($mail->to)->pluck('address')->sort()->values()->all() === ['jana@example.com', 'petr@example.com']);
    Mail::assertOutgoingCount(1);
});

test('emails nobody about an import that recorded revisions when there are no users', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = Ceskyflorbal::fixtureListSnapshotWithTime(1306757, '16:30');

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

    expect(app(ChangeSummaryWriter::class)->write($import))->not->toBeNull();
    Mail::assertNothingOutgoing();
});

test('emails nobody about the initial import', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    User::factory()->create();
    Ceskyflorbal::fake(Http::response(Ceskyflorbal::fixtureListSnapshot()));

    artisan('fixtures:import', ['teamSeason' => $teamSeason->id])->assertSuccessful();

    expect(Import::query()->sole()->fixtures_found)->toBe(24);
    Mail::assertNothingOutgoing();
});

test('emails nobody about an import that recorded no revisions', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    User::factory()->create();

    Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshot());

    Mail::assertNothingOutgoing();
});

test('emails nobody about an import whose only revision clears the rescheduled flag', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    User::factory()->create();
    $snapshot = str_replace(
        '<span aria-label="odložené utkání 17.10.2026" class="Tooltip Tooltip--warning u-mr-0 Tooltip--container"></span>',
        '',
        Ceskyflorbal::fixtureListSnapshot(),
    );

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

    expect($import->revisions()->count())->toBe(1);
    Mail::assertNothingOutgoing();
});
