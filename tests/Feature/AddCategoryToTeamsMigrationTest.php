<?php

declare(strict_types=1);

use App\Enums\Category;
use App\Models\Season;
use App\Models\Team;
use App\Models\TeamSeason;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Take the teams back to before they had a category and run the migration that gives them one.
 */
function giveExistingTeamsACategory(): void
{
    Schema::table('teams', function (Blueprint $table): void {
        $table->dropColumn('category');
    });

    (require database_path('migrations/2026_11_03_120000_add_category_to_teams_table.php'))->up();
}

test('guesses each existing team\'s category from the competition of its latest team season', function () {
    $pastSeason = Season::factory()->create(['name' => '2025/2026']);
    $currentSeason = Season::factory()->current()->create(['name' => '2026/2027']);
    $women = Team::factory()->create(['name' => 'FBC VHST Kutná Hora', 'slug' => 'fbc-vhst-kutna-hora']);
    TeamSeason::factory()->for($women)->for($currentSeason)->create(['competition_name' => '1. liga žen']);
    TeamSeason::factory()->for($women)->for($pastSeason)->create(['competition_name' => '2. liga juniorů - skupina 3']);
    $juniors = Team::factory()->create(['name' => 'FBC Kutná Hora', 'slug' => 'fbc-kutna-hora']);
    TeamSeason::factory()->for($juniors)->for($pastSeason)->create(['competition_name' => 'Národní liga']);
    TeamSeason::factory()->for($juniors)->for($currentSeason)->create(['competition_name' => '2. liga juniorů - skupina 3']);
    $men = Team::factory()->create(['name' => 'FBC VHST Kutná Hora muži', 'slug' => 'vhst-muzi']);
    TeamSeason::factory()->for($men)->for($pastSeason)->create(['competition_name' => '1. liga žen']);
    TeamSeason::factory()->for($men)->for($currentSeason)->create(['competition_name' => 'Národní liga']);

    giveExistingTeamsACategory();

    expect($women->fresh())
        ->category->toBe(Category::Women)
        ->slug->toBe('fbc-vhst-kutna-hora')
        ->and($juniors->fresh())
        ->category->toBe(Category::Juniors)
        ->slug->toBe('fbc-kutna-hora')
        ->and($men->fresh())
        ->category->toBe(Category::Men)
        ->slug->toBe('vhst-muzi');
});

test('gives men to an existing team without a competition', function () {
    $withoutTeamSeason = Team::factory()->create(['slug' => 'bez-sezony']);
    $notImported = Team::factory()->create(['slug' => 'nenacteny']);
    TeamSeason::factory()->for($notImported)->for(Season::factory()->state(['name' => '2025/2026']))->create(['competition_name' => '1. liga žen']);
    TeamSeason::factory()->for($notImported)->for(Season::factory()->current()->state(['name' => '2026/2027']))->notImported()->create();

    giveExistingTeamsACategory();

    expect($withoutTeamSeason->fresh())
        ->category->toBe(Category::Men)
        ->slug->toBe('bez-sezony')
        ->and($notImported->fresh())
        ->category->toBe(Category::Men)
        ->slug->toBe('nenacteny');
});

test('guesses the category from the category word in the competition name', function (string $competitionName, Category $category) {
    $team = Team::factory()->create();
    TeamSeason::factory()->for($team)->create(['competition_name' => $competitionName]);

    giveExistingTeamsACategory();

    expect($team->fresh()->category)->toBe($category);
})->with([
    ['Superliga', Category::Men],
    ['PH a SČ liga mužů', Category::Men],
    ['Extraliga žen', Category::Women],
    ['EXTRALIGA ZEN', Category::Women],
    ['Extraliga juniorů', Category::Juniors],
    ['1. liga juniorek', Category::JuniorWomen],
    ['Extraliga dorostenců', Category::Youths],
    ['2. liga dorostenek - skupina 1', Category::YouthWomen],
    ['Liga starších žáků - skupina 5', Category::OlderBoys],
    ['Liga starších žákyň', Category::OlderGirls],
    ['Liga mladších žáků', Category::YoungerBoys],
    ['Liga mladších žákyň', Category::YoungerGirls],
    ['Liga elévů', Category::Elves],
    ['Turnaj přípravky', Category::Preparatory],
]);
