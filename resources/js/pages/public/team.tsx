import { Head } from '@inertiajs/react';
import { cn } from 'cn';
import { CalendarClock, CalendarOff, MapPin } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import CalendarSubscription from '@/components/calendar-subscription';
import InAppBrowserNotice from '@/components/in-app-browser-notice';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { useIsInAppBrowser } from '@/hooks/use-in-app-browser';
import { recordTeamPageEvent } from '@/lib/team-page-events';
import type {
    CalendarLinks,
    MatchDay,
    TeamPageFixture,
    TeamPageTeamSeason,
} from '@/types';

/**
 * Match days shown before the player asks for the rest of the season, so the page stays short.
 */
const INITIAL_MATCH_DAYS = 4;

type Props = {
    slug: string;
    season: string | null;
    teamSeason: TeamPageTeamSeason | null;
    calendar: CalendarLinks;
};

export default function Team({ slug, season, teamSeason, calendar }: Props) {
    const isInAppBrowser = useIsInAppBrowser();

    // Effects run only in the browser, so a server-rendered page is counted once, after hydration.
    useEffect(() => recordTeamPageEvent(slug, 'page_view'), [slug]);

    return (
        <>
            <Head title={teamSeason?.name ?? 'Rozpis zápasů'} />
            <div className="min-h-screen bg-background text-foreground">
                <Hero
                    eyebrow={[teamSeason?.competition, season]
                        .filter(Boolean)
                        .join(' · ')}
                    title={teamSeason?.name ?? 'Rozpis zápasů'}
                />
                <main className="mx-auto grid w-full max-w-5xl gap-10 px-4 py-10 lg:grid-cols-[1fr_18rem]">
                    {/* First in the source so a phone shows how to subscribe before the schedule. */}
                    <aside
                        aria-labelledby="calendar-subscription"
                        className="min-w-0 lg:order-2"
                    >
                        <div className="flex flex-col gap-4 lg:sticky lg:top-6">
                            {isInAppBrowser && (
                                <InAppBrowserNotice slug={slug} />
                            )}
                            <CalendarSubscription
                                slug={slug}
                                calendar={calendar}
                            />
                        </div>
                    </aside>
                    <div className="flex min-w-0 flex-col gap-12 lg:order-1">
                        {teamSeason === null ? (
                            <EmptyState icon={CalendarClock}>
                                {season === null
                                    ? 'Zatím není rozpis'
                                    : `Pro sezonu ${season} zatím není rozpis`}
                            </EmptyState>
                        ) : (
                            <>
                                <Schedule matchDays={teamSeason.matchDays} />
                                <SourceFooter teamSeason={teamSeason} />
                            </>
                        )}
                    </div>
                </main>
            </div>
        </>
    );
}

function Hero({ eyebrow, title }: { eyebrow: string; title: string }) {
    return (
        <header className="bg-linear-to-br from-blue-700 via-blue-600 to-sky-500 text-white dark:from-blue-950 dark:via-blue-900 dark:to-sky-900">
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-4 px-4 py-10 sm:py-16">
                {eyebrow !== '' && (
                    <p className="text-sm font-medium tracking-widest uppercase opacity-80">
                        {eyebrow}
                    </p>
                )}
                <h1 className="text-4xl font-black tracking-tight break-words sm:text-5xl">
                    {title}
                </h1>
            </div>
        </header>
    );
}

function Schedule({ matchDays }: { matchDays: MatchDay[] }) {
    const [showsWholeSeason, setShowsWholeSeason] = useState(false);
    const shownMatchDays = showsWholeSeason
        ? matchDays
        : matchDays.slice(0, INITIAL_MATCH_DAYS);

    return (
        <section aria-labelledby="schedule" className="flex flex-col gap-4">
            <h2 id="schedule" className="text-2xl font-bold tracking-tight">
                Rozpis zápasů
            </h2>
            {matchDays.length === 0 ? (
                <EmptyState icon={CalendarOff}>Sezona skončila</EmptyState>
            ) : (
                <>
                    {shownMatchDays.map((matchDay, index) => (
                        <MatchDayCard
                            key={matchDay.date}
                            matchDay={matchDay}
                            isNext={index === 0}
                        />
                    ))}
                    {!showsWholeSeason &&
                        matchDays.length > INITIAL_MATCH_DAYS && (
                            <Button
                                variant="outline"
                                className="self-center"
                                onClick={() => setShowsWholeSeason(true)}
                            >
                                Zobrazit celou sezonu
                            </Button>
                        )}
                </>
            )}
        </section>
    );
}

/**
 * The list starts today, so its first match day is the next one the team plays.
 */
function MatchDayCard({
    matchDay,
    isNext,
}: {
    matchDay: MatchDay;
    isNext: boolean;
}) {
    const headingId = `match-day-${matchDay.date}`;

    return (
        <section aria-labelledby={headingId} data-test="match-day">
            <h3 id={headingId} className="sr-only">
                {matchDay.heading}
            </h3>
            <Card
                className={cn(
                    'flex-row gap-0 overflow-hidden py-0',
                    isNext && 'border-blue-600 ring-2 ring-blue-600/20',
                )}
            >
                <div
                    aria-hidden
                    className={cn(
                        'flex w-16 shrink-0 flex-col items-center justify-center gap-0.5 py-4 sm:w-20',
                        isNext ? 'bg-blue-600 text-white' : 'bg-muted',
                    )}
                >
                    <span className="text-xs font-semibold opacity-75">
                        {matchDay.dateTile.weekday}
                    </span>
                    <span className="text-3xl leading-none font-black tabular-nums">
                        {matchDay.dateTile.day}
                    </span>
                    <span className="text-xs opacity-75">
                        {matchDay.dateTile.month}
                    </span>
                </div>
                <div className="flex min-w-0 flex-1 flex-col gap-3 p-4">
                    {(isNext || matchDay.venue !== null) && (
                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                            {isNext && (
                                <Badge
                                    className="bg-blue-600 text-white"
                                    data-test="relative-day"
                                >
                                    {matchDay.relativeDay}
                                </Badge>
                            )}
                            {matchDay.venue !== null && (
                                <Venue name={matchDay.venue} />
                            )}
                        </div>
                    )}
                    <ul className="flex flex-col gap-3">
                        {matchDay.fixtures.map((fixture) => (
                            <FixtureItem key={fixture.id} fixture={fixture} />
                        ))}
                    </ul>
                </div>
            </Card>
        </section>
    );
}

function FixtureItem({ fixture }: { fixture: TeamPageFixture }) {
    const hasDetails =
        fixture.round !== null ||
        fixture.venue !== null ||
        fixture.statusLabel !== null;

    return (
        <li className="flex gap-3">
            <span className="w-12 shrink-0 font-semibold tabular-nums">
                {fixture.score ?? fixture.time ?? 'TBD'}
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
                {hasDetails && (
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {fixture.round !== null && <span>{fixture.round}</span>}
                        {fixture.venue !== null && (
                            <Venue name={fixture.venue} />
                        )}
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
                    </div>
                )}
            </div>
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

function SourceFooter({ teamSeason }: { teamSeason: TeamPageTeamSeason }) {
    return (
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
    );
}

function EmptyState({
    icon: Icon,
    children,
}: {
    icon: LucideIcon;
    children: string;
}) {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Icon aria-hidden />
                </EmptyMedia>
                <EmptyTitle>{children}</EmptyTitle>
            </EmptyHeader>
        </Empty>
    );
}
