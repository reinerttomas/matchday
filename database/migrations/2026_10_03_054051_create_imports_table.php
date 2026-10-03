<?php

declare(strict_types=1);

use App\Models\TeamSeason;
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
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TeamSeason::class)->constrained();
            $table->string('trigger');
            $table->string('status');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('fixtures_found')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('notified_at')->nullable();
        });
    }
};
