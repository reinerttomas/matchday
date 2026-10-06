import { Head } from '@inertiajs/react';
import { MapPin } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { MatchDay, TeamPageFixture, TeamPageTeamSeason } from '@/types';

/**
 * Match days shown before the player asks for the rest of the season, so the page stays short.
 */
const INITIAL_MATCH_DAYS = 4;

type Props = {
    season: string | null;
    teamSeason: TeamPageTeamSeason | null;
};

export default function Team({ season, teamSeason }: Props) {
    return (
        <>
            <Head title={teamSeason?.name ?? 'Rozpis zápasů'} />
            <div className="min-h-screen bg-background text-foreground">
                <main className="mx-auto flex w-full max-w-xl flex-col gap-8 px-4 py-8 sm:py-12">
                    {teamSeason === null ? (
                        <NoTeamSeason season={season} />
                    ) : (
                        <TeamSeasonSchedule
                            season={season}
                            teamSeason={teamSeason}
                        />
                    )}
                </main>
            </div>
        </>
    );
}

function NoTeamSeason({ season }: { season: string | null }) {
    return (
        <>
            <header>
                <h1 className="text-2xl font-semibold tracking-tight">
                    Rozpis zápasů
                </h1>
            </header>
            <EmptyState>
                {season === null
                    ? 'Zatím není rozpis.'
                    : `Pro sezonu ${season} zatím není rozpis.`}
            </EmptyState>
        </>
    );
}

function TeamSeasonSchedule({
    season,
    teamSeason,
}: {
    season: string | null;
    teamSeason: TeamPageTeamSeason;
}) {
    const [showsWholeSeason, setShowsWholeSeason] = useState(false);
    const matchDays = showsWholeSeason
        ? teamSeason.matchDays
        : teamSeason.matchDays.slice(0, INITIAL_MATCH_DAYS);

    return (
        <>
            <header className="space-y-1">
                <h1 className="text-2xl font-semibold tracking-tight break-words">
                    {teamSeason.name}
                </h1>
                <p className="text-sm text-muted-foreground">
                    {[teamSeason.competition, season]
                        .filter(Boolean)
                        .join(' · ')}
                </p>
            </header>

            {teamSeason.matchDays.length === 0 ? (
                <EmptyState>Sezona skončila.</EmptyState>
            ) : (
                <div className="flex flex-col gap-6">
                    {matchDays.map((matchDay) => (
                        <MatchDaySection
                            key={matchDay.date}
                            matchDay={matchDay}
                        />
                    ))}
                    {!showsWholeSeason &&
                        teamSeason.matchDays.length > INITIAL_MATCH_DAYS && (
                            <Button
                                variant="outline"
                                className="self-center"
                                onClick={() => setShowsWholeSeason(true)}
                            >
                                Zobrazit celou sezonu
                            </Button>
                        )}
                </div>
            )}

            <footer className="space-y-1 border-t pt-4 text-xs text-muted-foreground">
                <p>
                    Zdroj dat:{' '}
                    <a
                        href={teamSeason.sourceUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="underline underline-offset-4 hover:text-foreground"
                    >
                        rozpis zápasů na ceskyflorbal.cz
                    </a>
                </p>
                {teamSeason.lastImportedAt !== null && (
                    <p>Naposledy aktualizováno {teamSeason.lastImportedAt}.</p>
                )}
                <p>TBD znamená, že čas zápasu zatím nebyl stanoven.</p>
            </footer>
        </>
    );
}

function MatchDaySection({ matchDay }: { matchDay: MatchDay }) {
    const headingId = `match-day-${matchDay.date}`;

    return (
        <section aria-labelledby={headingId} data-test="match-day">
            <div className="mb-2 flex flex-wrap items-baseline gap-x-3 gap-y-0.5">
                <h2 id={headingId} className="font-semibold">
                    {matchDay.heading}
                </h2>
                {matchDay.venue !== null && <Venue name={matchDay.venue} />}
            </div>
            <ul className="divide-y rounded-lg border bg-card text-card-foreground">
                {matchDay.fixtures.map((fixture) => (
                    <FixtureItem key={fixture.id} fixture={fixture} />
                ))}
            </ul>
        </section>
    );
}

function FixtureItem({ fixture }: { fixture: TeamPageFixture }) {
    return (
        <li className="flex gap-3 px-3 py-3">
            <span className="w-12 shrink-0 font-medium tabular-nums">
                {fixture.time ?? 'TBD'}
            </span>
            <div className="min-w-0 flex-1 space-y-1">
                <p
                    className={cn(
                        'break-words',
                        fixture.status === 'cancelled' &&
                            'text-muted-foreground line-through',
                    )}
                >
                    {fixture.matchup.map((part, index) =>
                        part.isOurTeam ? (
                            <strong key={index} className="font-semibold">
                                {part.text}
                            </strong>
                        ) : (
                            <span key={index}>{part.text}</span>
                        ),
                    )}
                </p>
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted-foreground">
                    {fixture.statusLabel !== null && (
                        <Badge
                            variant={
                                fixture.status === 'cancelled'
                                    ? 'destructive'
                                    : 'secondary'
                            }
                        >
                            {fixture.statusLabel}
                        </Badge>
                    )}
                    {fixture.round !== null && <span>{fixture.round}</span>}
                    {fixture.venue !== null && <Venue name={fixture.venue} />}
                </div>
            </div>
            {fixture.score !== null && (
                <span className="shrink-0 font-semibold tabular-nums">
                    {fixture.score}
                </span>
            )}
        </li>
    );
}

function Venue({ name }: { name: string }) {
    return (
        <span className="inline-flex min-w-0 items-center gap-1 text-sm text-muted-foreground">
            <MapPin className="size-3.5 shrink-0" aria-hidden />
            <span className="break-words">{name}</span>
        </span>
    );
}

function EmptyState({ children }: { children: string }) {
    return (
        <p className="rounded-lg border border-dashed px-4 py-8 text-center text-muted-foreground">
            {children}
        </p>
    );
}
