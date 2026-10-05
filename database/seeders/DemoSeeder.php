<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FixtureStatus;
use App\Enums\RevisionField;
use App\Models\Fixture;
use App\Models\Import;
use App\Models\Revision;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use App\Models\Venue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds FBC Kutná Hora B's fixture lists and import history so that every page can be developed on believable data.
 *
 * Fixture dates are fixed to the seasons, while import times are relative to today, so the "changed in the last 7 days" window always has data.
 */
final class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teamName = 'FBC Kutná Hora B';
        $team = Team::factory()->create(['slug' => Str::slug($teamName)]);
        $pastSeason = Season::factory()->create(['name' => '2025/26']);
        $currentSeason = Season::factory()->current()->create(['name' => '2026/27']);

        $venues = $this->seedVenues();

        $this->seedPastTeamSeason(
            TeamSeason::factory()->for($team)->for($pastSeason)->create([
                'external_id' => 42058,
                'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/42058',
                'name' => $teamName,
                'competition_name' => 'PH a SČ liga mužů',
            ]),
            $venues,
        );

        $this->seedCurrentTeamSeason(
            TeamSeason::factory()->for($team)->for($currentSeason)->create([
                'external_id' => 45019,
                'source_url' => 'https://www.ceskyflorbal.cz/team/detail/matches/45019',
                'name' => $teamName,
                'competition_name' => 'PH a SČ liga mužů',
            ]),
            $venues,
        );
    }

    /**
     * Seed the venues, some of whose addresses have not been read from a fixture detail page yet.
     *
     * @return array{kutnaHora: Venue, zizkov: Venue, mladaBoleslav: Venue, kolin: Venue, benesov: Venue, caslav: Venue, kladno: Venue}
     */
    private function seedVenues(): array
    {
        return [
            'kutnaHora' => Venue::factory()->create(['external_id' => 1402, 'name' => 'Sportovní hala Kutná Hora', 'address' => 'Čáslavská 274, Kutná Hora']),
            'zizkov' => Venue::factory()->withoutAddress()->create(['external_id' => 1789, 'name' => 'Tělocvična ZŠ Žižkov Kutná Hora']),
            'mladaBoleslav' => Venue::factory()->create(['external_id' => 1133, 'name' => 'Sportovní hala Mladá Boleslav', 'address' => 'Jaselská 1352, Mladá Boleslav']),
            'kolin' => Venue::factory()->withoutAddress()->create(['external_id' => 1257, 'name' => 'Hala Kolín']),
            'benesov' => Venue::factory()->create(['external_id' => 1520, 'name' => 'Sportovní hala Benešov', 'address' => 'Hráského 2231, Benešov']),
            'caslav' => Venue::factory()->create(['external_id' => 1311, 'name' => 'Sportovní hala Čáslav', 'address' => 'Masarykova 1, Čáslav']),
            'kladno' => Venue::factory()->withoutAddress()->create(['external_id' => 1098, 'name' => 'Sportovní hala Kladno']),
        ];
    }

    /**
     * Seed a handful of finished fixtures of last season, so that past-season browsing has data.
     *
     * @param  array{kutnaHora: Venue, zizkov: Venue, mladaBoleslav: Venue, kolin: Venue, benesov: Venue, caslav: Venue, kladno: Venue}  $venues
     */
    private function seedPastTeamSeason(TeamSeason $teamSeason, array $venues): void
    {
        $fixtures = Fixture::factory()
            ->for($teamSeason)
            ->finished()
            ->createMany([
                ['external_id' => 1287401, 'round' => 1, 'is_home' => true, 'opponent_name' => 'FBC Říčany', 'date' => '2025-10-05', 'time' => '10:00:00', 'venue_id' => $venues['kutnaHora']->id, 'home_score' => 5, 'away_score' => 2],
                ['external_id' => 1287404, 'round' => 4, 'is_home' => false, 'opponent_name' => 'Florbal Kolín', 'date' => '2025-10-19', 'time' => '12:00:00', 'venue_id' => $venues['kolin']->id, 'home_score' => 6, 'away_score' => 4],
                ['external_id' => 1287407, 'round' => 7, 'is_home' => true, 'opponent_name' => 'SK Čáslav', 'date' => '2025-11-16', 'time' => '09:00:00', 'venue_id' => $venues['kutnaHora']->id, 'home_score' => 3, 'away_score' => 3],
                ['external_id' => 1287410, 'round' => 10, 'is_home' => false, 'opponent_name' => 'FBC Benešov', 'date' => '2025-12-07', 'time' => '11:00:00', 'venue_id' => $venues['benesov']->id, 'home_score' => 2, 'away_score' => 7],
                ['external_id' => 1287414, 'round' => 14, 'is_home' => true, 'opponent_name' => 'FBK Kladno B', 'date' => '2026-01-18', 'time' => '13:00:00', 'venue_id' => $venues['kutnaHora']->id, 'home_score' => 1, 'away_score' => 4],
                ['external_id' => 1287418, 'round' => 18, 'is_home' => false, 'opponent_name' => 'FbC Mladá Boleslav B', 'date' => '2026-02-15', 'time' => '10:00:00', 'venue_id' => $venues['mladaBoleslav']->id, 'home_score' => 5, 'away_score' => 3],
            ]);

        Import::factory()->for($teamSeason)->create([
            'started_at' => '2026-02-16 06:00:00',
            'fixtures_found' => $fixtures->count(),
        ]);
    }

    /**
     * Seed the current season's fixture list in every state, and the imports and revisions that led to it.
     *
     * @param  array{kutnaHora: Venue, zizkov: Venue, mladaBoleslav: Venue, kolin: Venue, benesov: Venue, caslav: Venue, kladno: Venue}  $venues
     */
    private function seedCurrentTeamSeason(TeamSeason $teamSeason, array $venues): void
    {
        $fixtures = Fixture::factory()
            ->for($teamSeason)
            ->createMany([
                ['external_id' => 1395851, 'round' => 1, 'is_home' => true, 'opponent_name' => 'FBC Říčany', 'date' => '2026-09-06', 'time' => '10:00:00', 'venue_id' => $venues['kutnaHora']->id, 'status' => FixtureStatus::Finished, 'home_score' => 6, 'away_score' => 3],
                ['external_id' => 1395852, 'round' => 2, 'is_home' => true, 'opponent_name' => 'Sokol Brandýs nad Labem', 'date' => '2026-09-06', 'time' => '13:00:00', 'venue_id' => $venues['kutnaHora']->id, 'status' => FixtureStatus::Finished, 'home_score' => 4, 'away_score' => 4],
                ['external_id' => 1395853, 'round' => 3, 'is_home' => false, 'opponent_name' => 'FbC Mladá Boleslav B', 'date' => '2026-09-20', 'time' => '09:00:00', 'venue_id' => $venues['mladaBoleslav']->id, 'status' => FixtureStatus::Finished, 'home_score' => 7, 'away_score' => 2],
                ['external_id' => 1395854, 'round' => 4, 'is_home' => false, 'opponent_name' => 'Florbal Kolín', 'date' => '2026-09-20', 'time' => '12:00:00', 'venue_id' => $venues['mladaBoleslav']->id, 'status' => FixtureStatus::Finished, 'home_score' => 3, 'away_score' => 5],
                ['external_id' => 1395855, 'round' => 5, 'is_home' => false, 'opponent_name' => 'FBK Kladno B', 'date' => '2026-10-04', 'time' => '10:00:00', 'venue_id' => $venues['kolin']->id, 'status' => FixtureStatus::Finished, 'home_score' => 8, 'away_score' => 2, 'sequence' => 1],
                ['external_id' => 1395856, 'round' => 6, 'is_home' => true, 'opponent_name' => 'Tigers Jižní Město C', 'date' => '2026-10-04', 'time' => '13:00:00', 'venue_id' => $venues['kolin']->id, 'status' => FixtureStatus::Finished, 'home_score' => 5, 'away_score' => 1, 'sequence' => 1],
                ['external_id' => 1395857, 'round' => 7, 'is_home' => true, 'opponent_name' => 'Las Plantas', 'date' => '2026-11-25', 'time' => '19:30:00', 'venue_id' => $venues['zizkov']->id, 'is_rescheduled' => true, 'sequence' => 1],
                ['external_id' => 1395858, 'round' => 8, 'is_home' => true, 'opponent_name' => 'FBC Benešov', 'date' => '2026-10-17', 'time' => '17:00:00', 'venue_id' => $venues['kutnaHora']->id],
                ['external_id' => 1395859, 'round' => 9, 'is_home' => false, 'opponent_name' => 'FbŠ Bohemians D', 'date' => '2026-11-01', 'time' => '11:00:00', 'venue_id' => $venues['benesov']->id],
                ['external_id' => 1395860, 'round' => 10, 'is_home' => false, 'opponent_name' => 'Florbal Nymburk', 'date' => '2026-11-01', 'time' => '14:00:00', 'venue_id' => $venues['benesov']->id],
                ['external_id' => 1395861, 'round' => 11, 'is_home' => true, 'opponent_name' => 'TBC Engineers Horoměřice', 'date' => '2026-11-15', 'time' => '09:00:00', 'venue_id' => $venues['kutnaHora']->id, 'sequence' => 1],
                ['external_id' => 1395862, 'round' => 12, 'is_home' => true, 'opponent_name' => 'SK Čáslav', 'date' => '2026-11-15', 'time' => '11:30:00', 'venue_id' => $venues['kutnaHora']->id],
                ['external_id' => 1395863, 'round' => 13, 'is_home' => false, 'opponent_name' => 'FBC Říčany', 'date' => '2026-11-29', 'time' => '10:00:00', 'venue_id' => $venues['caslav']->id, 'status' => FixtureStatus::Postponed, 'sequence' => 1],
                ['external_id' => 1395864, 'round' => 14, 'is_home' => false, 'opponent_name' => 'Sokol Brandýs nad Labem', 'date' => '2026-11-29', 'time' => '13:00:00', 'venue_id' => $venues['caslav']->id],
                ['external_id' => 1395865, 'round' => 15, 'is_home' => true, 'opponent_name' => 'FbC Mladá Boleslav B', 'date' => '2026-12-13', 'time' => '10:30:00', 'venue_id' => $venues['mladaBoleslav']->id, 'sequence' => 1],
                ['external_id' => 1395866, 'round' => 16, 'is_home' => true, 'opponent_name' => 'Florbal Kolín', 'date' => '2026-12-13', 'time' => null, 'venue_id' => $venues['mladaBoleslav']->id],
                ['external_id' => 1395867, 'round' => 17, 'is_home' => true, 'opponent_name' => 'FBK Kladno B', 'date' => '2027-01-10', 'time' => null, 'venue_id' => $venues['kutnaHora']->id],
                ['external_id' => 1395868, 'round' => 18, 'is_home' => false, 'opponent_name' => 'Tigers Jižní Město C', 'date' => '2027-01-10', 'time' => null, 'venue_id' => $venues['kutnaHora']->id],
                ['external_id' => 1395869, 'round' => 19, 'is_home' => false, 'opponent_name' => 'Las Plantas', 'date' => '2027-01-24', 'time' => null, 'venue_id' => $venues['kladno']->id],
                ['external_id' => 1395870, 'round' => 20, 'is_home' => false, 'opponent_name' => 'FBC Benešov', 'date' => '2027-01-24', 'time' => null, 'venue_id' => $venues['kladno']->id, 'status' => FixtureStatus::Cancelled, 'sequence' => 1],
                ['external_id' => 1395871, 'round' => 21, 'is_home' => true, 'opponent_name' => 'FbŠ Bohemians D', 'date' => '2027-02-07', 'time' => null, 'venue_id' => $venues['benesov']->id],
                ['external_id' => 1395872, 'round' => 22, 'is_home' => true, 'opponent_name' => 'Florbal Nymburk', 'date' => '2027-02-07', 'time' => null, 'venue_id' => $venues['benesov']->id],
                ['external_id' => 1395873, 'round' => 23, 'is_home' => false, 'opponent_name' => 'TBC Engineers Horoměřice', 'date' => '2027-02-21', 'time' => null, 'venue_id' => $venues['kutnaHora']->id],
                ['external_id' => 1412036, 'round' => 24, 'is_home' => false, 'opponent_name' => 'SK Čáslav', 'date' => '2027-02-21', 'time' => null, 'venue_id' => $venues['kutnaHora']->id],
            ])
            ->keyBy('round');

        // The administrator added the team season by hand, which started its initial import.
        Import::factory()->for($teamSeason)->manual()->create([
            'started_at' => today()->subDays(12)->setTime(17, 42),
            'fixtures_found' => 23,
        ]);

        Import::factory()->for($teamSeason)->create([
            'started_at' => today()->subDays(12)->setTime(21, 0),
            'fixtures_found' => 23,
        ]);

        $notifiedImport = Import::factory()->for($teamSeason)->notified()->create([
            'started_at' => today()->subDays(8)->setTime(6, 0),
            'fixtures_found' => 23,
        ]);
        $this->recordFieldChange($notifiedImport, $fixtures[7], RevisionField::Date, '2026-10-17', '2026-11-25');
        $this->recordFieldChange($notifiedImport, $fixtures[7], RevisionField::Venue, $venues['kutnaHora']->name, $venues['zizkov']->name);
        $this->recordFieldChange($notifiedImport, $fixtures[7], RevisionField::IsRescheduled, '0', '1');
        $this->recordFieldChange($notifiedImport, $fixtures[11], RevisionField::Time, null, '09:00');
        $this->recordFieldChange($notifiedImport, $fixtures[20], RevisionField::Status, FixtureStatus::Scheduled->value, FixtureStatus::Cancelled->value);

        Import::factory()->for($teamSeason)->error()->create([
            'started_at' => today()->subDays(5)->setTime(12, 0),
        ]);

        Import::factory()->for($teamSeason)->create([
            'started_at' => today()->subDays(5)->setTime(15, 0),
            'fixtures_found' => 23,
        ]);

        $unnotifiedImport = Import::factory()->for($teamSeason)->create([
            'started_at' => today()->subDays(2)->setTime(9, 0),
            'fixtures_found' => 24,
        ]);
        $this->recordFieldChange($unnotifiedImport, $fixtures[13], RevisionField::Status, FixtureStatus::Scheduled->value, FixtureStatus::Postponed->value);
        $this->recordFieldChange($unnotifiedImport, $fixtures[15], RevisionField::Time, null, '10:30');
        Revision::factory()->for($unnotifiedImport)->for($fixtures[24])->fixtureAdded()->create(['created_at' => $unnotifiedImport->finished_at]);

        Import::factory()->for($teamSeason)->manual()->create([
            'started_at' => today()->subDay()->setTime(8, 15),
            'fixtures_found' => 24,
        ]);

        Import::factory()->for($teamSeason)->aborted()->create([
            'started_at' => today()->subDay()->setTime(12, 0),
        ]);

        // Left unsent like the import before it, so the sidebar shows more than one unsent change summary.
        $resultsImport = Import::factory()->for($teamSeason)->create([
            'started_at' => today()->subDay()->setTime(15, 0),
            'fixtures_found' => 24,
        ]);
        $this->recordFieldChange($resultsImport, $fixtures[5], RevisionField::Status, FixtureStatus::Scheduled->value, FixtureStatus::Finished->value);
        $this->recordFieldChange($resultsImport, $fixtures[5], RevisionField::HomeScore, null, '8');
        $this->recordFieldChange($resultsImport, $fixtures[5], RevisionField::AwayScore, null, '2');
        $this->recordFieldChange($resultsImport, $fixtures[6], RevisionField::Status, FixtureStatus::Scheduled->value, FixtureStatus::Finished->value);
        $this->recordFieldChange($resultsImport, $fixtures[6], RevisionField::HomeScore, null, '5');
        $this->recordFieldChange($resultsImport, $fixtures[6], RevisionField::AwayScore, null, '1');
    }

    /**
     * Record a revision of one fixture field, detected by the import.
     */
    private function recordFieldChange(Import $import, Fixture $fixture, RevisionField $field, ?string $oldValue, ?string $newValue): void
    {
        Revision::factory()
            ->for($import)
            ->for($fixture)
            ->fieldChange($field, $oldValue, $newValue)
            ->create(['created_at' => $import->finished_at]);
    }
}
