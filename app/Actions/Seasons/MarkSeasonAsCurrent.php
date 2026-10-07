<?php

declare(strict_types=1);

namespace App\Actions\Seasons;

use App\Models\Season;
use Illuminate\Support\Facades\DB;

final readonly class MarkSeasonAsCurrent
{
    /**
     * Make the season the one every calendar, public page and automatic import uses; the previously current season stops being current in the same transaction, so exactly one season is current.
     */
    public function handle(Season $season): void
    {
        DB::transaction(function () use ($season): void {
            Season::query()
                ->where('is_current', true)
                ->whereKeyNot($season->id)
                ->update(['is_current' => false]);

            $season->update(['is_current' => true]);
        });
    }
}
