<?php

declare(strict_types=1);

use App\Enums\Category;
use App\Services\CategoryGuesser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give every team a category, guessed for existing teams from the competition of their latest team season.
     *
     * The column is added as required with men as its default rather than nullable and made required afterwards: SQLite can only
     * make a column required by rebuilding the table, and dropping the old teams table inside the migration's transaction fails on
     * the team seasons that reference it. Men is also what a team gets when the guess finds nothing. Slugs stay as they are (ADR 0003).
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table): void {
            $table->string('category')->default(Category::Men->value)->after('name');
        });

        $categoryGuesser = new CategoryGuesser;

        // The query builder rather than the models, so the migration keeps working once the models change.

        DB::table('teams')->orderBy('id')->each(function (object $team) use ($categoryGuesser): void {
            $competitionName = DB::table('team_seasons')
                ->join('seasons', 'seasons.id', '=', 'team_seasons.season_id')
                ->where('team_seasons.team_id', $team->id)
                ->orderByDesc('seasons.name')
                ->value('team_seasons.competition_name');

            if (is_string($competitionName)) {
                DB::table('teams')
                    ->where('id', $team->id)
                    ->update(['category' => $categoryGuesser->guess($competitionName)->value]);
            }
        });
    }
};
