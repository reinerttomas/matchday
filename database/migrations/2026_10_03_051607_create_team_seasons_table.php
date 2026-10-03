<?php

declare(strict_types=1);

use App\Models\Season;
use App\Models\Team;
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
        Schema::create('team_seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Team::class)->constrained();
            $table->foreignIdFor(Season::class)->constrained();
            $table->unsignedBigInteger('external_id')->unique();
            $table->string('source_url');
            $table->string('name')->nullable();
            $table->string('competition_name')->nullable();
            $table->boolean('auto_import_enabled')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'season_id']);
        });
    }
};
