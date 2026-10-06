<?php

declare(strict_types=1);

use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Services\ChangeSummaryWriter;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\Ceskyflorbal;

/**
 * The change summary of FBC Kutná Hora B with the given bullets, as the team's WhatsApp group receives it.
 */
function kutnaHoraChangeSummary(string ...$bullets): string
{
    return implode("\n", [
        '📅 Změny v rozpisu FBC Kutná Hora B',
        ...$bullets,
        '',
        'Kalendář: '.url('t/kutna-hora-b'),
    ]);
}

/**
 * The change summary of the import as the app writes it.
 */
function changeSummaryOf(Import $import): ?string
{
    return app(ChangeSummaryWriter::class)->write($import);
}

test('announces a start time set for a fixture with a TBD time', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshotWithTime(1306783, '09:00'));

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• NE 15. 11. FBC Kutná Hora B – TBC Engineers Horoměřice: čas doplněn 9:00',
    ));
});

test('announces a changed start time', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshotWithTime(1306757, '16:30'));

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: nový čas 16:30 (původně 15:00)',
    ));
});

test('announces a start time that went back to TBD', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshotWithTime(1306757, '00:00'));

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: čas TBD (původně 15:00)',
    ));
});

test('announces a fixture rescheduled to a new date with its original date', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = Str::of(Ceskyflorbal::fixtureListSnapshot())
        ->replace('SO, 17. 10.', 'SO, 24. 10.')
        ->replace(
            '<p class="Match-place">Unihoc Aréna Praha</p>',
            '<p class="Match-place">Unihoc Aréna Praha</p><span aria-label="odložené utkání 17.10.2026" class="Tooltip Tooltip--warning u-mr-0 Tooltip--container"></span>',
        )
        ->toString();

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 24. 10. Tatran Střešovice C – FBC Kutná Hora B: přeloženo, nový termín 24. 10. 2026 (původně 17. 10. 2026)',
    ));
});

test('announces a fixture newly marked as rescheduled without a new date', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<p class="Match-place">Unihoc Aréna Praha</p>',
        '<p class="Match-place">Unihoc Aréna Praha</p><span aria-label="odložené utkání 17.10.2026" class="Tooltip Tooltip--warning u-mr-0 Tooltip--container"></span>',
        Ceskyflorbal::fixtureListSnapshot(),
    );

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: nově dohrávka',
    ));
});

test('writes no summary when the only revision clears the rescheduled flag', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<span aria-label="odložené utkání 17.10.2026" class="Tooltip Tooltip--warning u-mr-0 Tooltip--container"></span>',
        '',
        Ceskyflorbal::fixtureListSnapshot(),
    );

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

    expect($import->revisions()->count())->toBe(1)
        ->and(changeSummaryOf($import))->toBeNull();
});

test('announces a fixture moved to another venue', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = str_replace(
        '<p class="Match-place">SH Kutná Hora Klimeška</p>',
        '<p class="Match-place">SH Kutná Hora Šipší</p>',
        Ceskyflorbal::fixtureListSnapshot(),
    );

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot, [
        Ceskyflorbal::MATCH_DETAIL_URL.'1306754' => Http::sequence([
            Http::response(Ceskyflorbal::matchDetailSnapshot()),
            Http::response(Ceskyflorbal::matchDetailSnapshot('SH Kutná Hora Šipší', 731)),
        ]),
    ]);

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• ST 25. 11. FBC Kutná Hora B – Las Plantas: nová hala SH Kutná Hora Šipší (původně SH Kutná Hora Klimeška)',
    ));
});

test('announces a finished fixture with its score', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshotWithFinishedFixture());

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: odehráno 5:3',
    ));
});

test('announces a score that appeared while the status stayed the same', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $snapshot = str_replace('odehráno', 'kontumace', Ceskyflorbal::fixtureListSnapshotWithFinishedFixture());

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $snapshot);

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: skóre 5:3',
    ));
});

test('announces a fixture cancelled after missing from two consecutive imports', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithoutFixture = Ceskyflorbal::fixtureListSnapshotWithout(1306757);

    [, , $import] = Ceskyflorbal::importFixtureListPagesInTurn($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $fixtureListWithoutFixture, $fixtureListWithoutFixture);

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: zrušeno',
    ));
});

test('announces a cancelled fixture that reappeared', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithoutFixture = Ceskyflorbal::fixtureListSnapshotWithout(1306757);

    [, , , $import] = Ceskyflorbal::importFixtureListPagesInTurn(
        $teamSeason,
        Ceskyflorbal::fixtureListSnapshot(),
        $fixtureListWithoutFixture,
        $fixtureListWithoutFixture,
        Ceskyflorbal::fixtureListSnapshot(),
    );

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: znovu v rozpisu',
    ));
});

test('announces a fixture added after the initial import with its time and venue', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshotWithout(1306796), Ceskyflorbal::fixtureListSnapshot());

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• NE 29. 11. VSK MFF UK Praha – FBC Kutná Hora B: nový zápas v rozpisu, čas TBD, Marsch Arena',
    ));
});

test('combines the revisions of one fixture into one bullet and orders the bullets by date', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithoutFixture = Ceskyflorbal::fixtureListSnapshotWithout(1306757);
    $revisedFixtureList = Str::of(Ceskyflorbal::fixtureListSnapshotWithTime(1306754, '18:00', $fixtureListWithoutFixture))
        ->replace('ST, 25. 11.', 'ST, 2. 12.')
        ->replace('<span aria-label="odložené utkání 17.10.2026" class="Tooltip Tooltip--warning u-mr-0 Tooltip--container"></span>', '')
        ->toString();

    // The fixture cancelled for missing is revised after every fixture the list shows, so its bullet is recorded last.
    [, , $import] = Ceskyflorbal::importFixtureListPagesInTurn($teamSeason, Ceskyflorbal::fixtureListSnapshot(), $fixtureListWithoutFixture, $revisedFixtureList);

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: zrušeno',
        '• ST 2. 12. FBC Kutná Hora B – Las Plantas: přeloženo, nový termín 2. 12. 2026 (původně 25. 11. 2026); čas doplněn 18:00',
    ));
});

test('shows a fixture as it was right after the import even after a later import moved it', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithChangedTime = Ceskyflorbal::fixtureListSnapshotWithTime(1306757, '16:30');

    [, $timeChangingImport, $dateChangingImport] = Ceskyflorbal::importFixtureListPagesInTurn(
        $teamSeason,
        Ceskyflorbal::fixtureListSnapshot(),
        $fixtureListWithChangedTime,
        str_replace('SO, 17. 10.', 'SO, 24. 10.', $fixtureListWithChangedTime),
    );

    expect(changeSummaryOf($timeChangingImport))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: nový čas 16:30 (původně 15:00)',
    ));
    expect(changeSummaryOf($dateChangingImport))->toBe(kutnaHoraChangeSummary(
        '• SO 24. 10. Tatran Střešovice C – FBC Kutná Hora B: přeloženo, nový termín 24. 10. 2026 (původně 17. 10. 2026)',
    ));
});

test('shows an added fixture as it was right after the import even after a later import cancelled it', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $fixtureListWithoutFixture = Ceskyflorbal::fixtureListSnapshotWithout(1306796);

    [, $addingImport, , $cancellingImport] = Ceskyflorbal::importFixtureListPagesInTurn(
        $teamSeason,
        $fixtureListWithoutFixture,
        Ceskyflorbal::fixtureListSnapshot(),
        $fixtureListWithoutFixture,
        $fixtureListWithoutFixture,
    );

    expect(changeSummaryOf($addingImport))->toBe(kutnaHoraChangeSummary(
        '• NE 29. 11. VSK MFF UK Praha – FBC Kutná Hora B: nový zápas v rozpisu, čas TBD, Marsch Arena',
    ));
    expect(changeSummaryOf($cancellingImport))->toBe(kutnaHoraChangeSummary(
        '• NE 29. 11. VSK MFF UK Praha – FBC Kutná Hora B: zrušeno',
    ));
});

test('announces a postponed fixture', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $teamSeason->update(['name' => 'FBC Kutná Hora B']);
    $import = Import::factory()->for($teamSeason)->create();
    $fixture = Fixture::factory()->for($teamSeason)->home()->postponed()->create(['opponent_name' => 'Las Plantas', 'date' => '2026-11-25']);
    Revision::factory()->for($import)->for($fixture)->fieldChange(RevisionField::Status, 'scheduled', 'postponed')->create();

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• ST 25. 11. FBC Kutná Hora B – Las Plantas: odloženo, nový termín zatím není známý',
    ));
});

test('announces an added fixture by its time even when it is stored as scheduled with a score', function () {
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();
    $teamSeason->update(['name' => 'FBC Kutná Hora B']);
    $import = Import::factory()->for($teamSeason)->create();
    $fixture = Fixture::factory()->for($teamSeason)->away()->create([
        'opponent_name' => 'Tatran Střešovice C',
        'date' => '2026-10-17',
        'time' => '15:00:00',
        'venue_id' => null,
        'home_score' => 5,
        'away_score' => 3,
    ]);
    Revision::factory()->for($import)->for($fixture)->fixtureAdded()->create();

    expect(changeSummaryOf($import))->toBe(kutnaHoraChangeSummary(
        '• SO 17. 10. Tatran Střešovice C – FBC Kutná Hora B: nový zápas v rozpisu, 15:00',
    ));
});

test('writes Czech weekdays whatever the app locale', function () {
    App::setLocale('de');
    $teamSeason = Ceskyflorbal::kutnaHoraTeamSeason();

    $import = Ceskyflorbal::importTwice($teamSeason, Ceskyflorbal::fixtureListSnapshot(), Ceskyflorbal::fixtureListSnapshotWithTime(1306783, '09:00'));

    expect(changeSummaryOf($import))->toContain('• NE 15. 11. ');
});

test('links to a WhatsApp chat prefilled with the URL-encoded summary', function () {
    $whatsAppUrl = app(ChangeSummaryWriter::class)->whatsAppUrl("📅 Změny\n• A & B");

    expect($whatsAppUrl)->toBe('https://wa.me/?text=%F0%9F%93%85%20Zm%C4%9Bny%0A%E2%80%A2%20A%20%26%20B');
});
