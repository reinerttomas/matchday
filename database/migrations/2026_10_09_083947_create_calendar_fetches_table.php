<?php

declare(strict_types=1);

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
        // One row per team, day and User-Agent instead of per fetch, because calendar apps poll the feed every few hours.
        Schema::create('calendar_fetches', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Team::class)->constrained();
            $table->date('date')->index();
            $table->string('user_agent', 512);
            // The unique index needs a short key; the User-Agent itself is too long for it.
            $table->char('user_agent_hash', 64);
            $table->unsignedInteger('count');

            $table->unique(['team_id', 'date', 'user_agent_hash']);
        });
    }
};
