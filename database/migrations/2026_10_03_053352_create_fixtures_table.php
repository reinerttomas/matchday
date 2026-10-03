<?php

declare(strict_types=1);

use App\Models\TeamSeason;
use App\Models\Venue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TeamSeason::class)->constrained();
            $table->unsignedBigInteger('external_id');
            $table->unsignedSmallInteger('round')->nullable();
            $table->boolean('is_home');
            $table->string('opponent_name');
            $table->foreignIdFor(Venue::class)->nullable()->constrained();
            // Local Europe/Prague wall-clock values as the federation publishes them; a null time means TBD.
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('status');
            $table->boolean('is_rescheduled')->default(false);
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->unsignedInteger('sequence')->default(0);
            $table->unsignedInteger('missing_count')->default(0);
            $table->timestamps();

            $table->unique(['team_season_id', 'external_id']);
            $table->index(['team_season_id', 'date']);
        });
    }
};
