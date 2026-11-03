<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_subscriptions', function (Blueprint $table): void {
            $table->id();
            // A notification subscription means nothing without its administrator or team, so it goes with either.
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Team::class)->constrained()->cascadeOnDelete();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'team_id']);
        });

        /**
         * Subscribe every existing administrator to every existing team, so nobody loses a change summary on deploy.
         * The query builder instead of the models keeps the migration independent of later changes to them.
         */
        $now = now();
        DB::table('notification_subscriptions')->insertUsing(
            ['user_id', 'team_id', 'created_at', 'updated_at'],
            fn (Builder $query): Builder => $query->from('users')
                ->crossJoin('teams')
                ->select(['users.id', 'teams.id'])
                ->selectRaw('?, ?', [$now, $now]),
        );
    }
};
