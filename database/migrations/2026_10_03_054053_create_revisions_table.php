<?php

declare(strict_types=1);

use App\Models\Fixture;
use App\Models\Import;
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
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Fixture::class)->constrained();
            $table->foreignIdFor(Import::class)->constrained();
            // A null field records a fixture that appeared after the team season's initial import (ADR-0001).
            $table->string('field')->nullable();
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
