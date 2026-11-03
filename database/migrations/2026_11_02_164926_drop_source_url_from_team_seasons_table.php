<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every address of the federation team page is derived from the team season's external_id, so the stored fixture list address goes.
     */
    public function up(): void
    {
        Schema::table('team_seasons', function (Blueprint $table): void {
            $table->dropColumn('source_url');
        });
    }
};
