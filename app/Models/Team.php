<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Category;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read Category $category
 * @property-read string $slug
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read Collection<int, TeamSeason> $teamSeasons
 * @property-read TeamSeason|null $currentTeamSeason
 * @property-read Collection<int, NotificationSubscription> $notificationSubscriptions
 * @property-read Collection<int, User> $subscribedUsers
 */
#[Fillable(['name', 'category', 'slug'])]
final class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * Get the team's participations in seasons.
     *
     * @return HasMany<TeamSeason, $this>
     */
    public function teamSeasons(): HasMany
    {
        return $this->hasMany(TeamSeason::class);
    }

    /**
     * Get the team's participation in the current season, which the calendar feed and the public team page show.
     *
     * @return HasOne<TeamSeason, $this>
     */
    public function currentTeamSeason(): HasOne
    {
        return $this->hasOne(TeamSeason::class)
            ->whereRelation('season', 'is_current', true)
            ->chaperone();
    }

    /**
     * Get the team's participation in its latest season by season order, whether or not that season is the current one.
     */
    public function latestTeamSeason(): ?TeamSeason
    {
        return $this->teamSeasons
            ->sortByDesc(fn (TeamSeason $teamSeason): string => $teamSeason->season->name)
            ->first();
    }

    /**
     * @return HasMany<NotificationSubscription, $this>
     */
    public function notificationSubscriptions(): HasMany
    {
        return $this->hasMany(NotificationSubscription::class);
    }

    /**
     * Get the administrators who receive the team's change summaries.
     *
     * @return BelongsToMany<User, $this>
     */
    public function subscribedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_subscriptions')
            ->wherePivotNull('unsubscribed_at');
    }

    /**
     * Name the team's calendar after the team and its category, such as "FBC VHST Kutná Hora (ženy)", so two subscribed calendars of same-named teams can be told apart.
     */
    public function calendarName(): string
    {
        return "{$this->name} ({$this->category->label()})";
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => Category::class,
        ];
    }
}
