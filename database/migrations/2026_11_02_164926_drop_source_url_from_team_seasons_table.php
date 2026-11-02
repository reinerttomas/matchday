<?php

declare(strict_types=1);

use App\Services\Ceskyflorbal\FederationTeamPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

    /**
     * Unlike the app's other migrations this one goes back, since nothing is lost: the column returns filled with the fixture list address derived from external_id.
     */
    public function down(): void
    {
        Schema::table('team_seasons', function (Blueprint $table): void {
            $table->string('source_url')->nullable()->after('external_id');
        });

        $federationTeamPage = app(FederationTeamPage::class);

        // The query builder, not the model, so the migration keeps working whatever the model later becomes.
        DB::table('team_seasons')->select(['id', 'external_id'])->lazyById()->each(function (object $teamSeason) use ($federationTeamPage): void {
            DB::table('team_seasons')->where('id', $teamSeason->id)->update([
                'source_url' => $federationTeamPage->fixtureListAddress((int) $teamSeason->external_id),
            ]);
        });

        Schema::table('team_seasons', function (Blueprint $table): void {
            $table->string('source_url')->nullable(false)->change();
        });
    }
};
