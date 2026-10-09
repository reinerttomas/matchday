import { Head, Link, router } from '@inertiajs/react';
import { cn } from 'cn';
import {
    AlertTriangle,
    CalendarOff,
    ChevronDown,
    DownloadCloud,
    ExternalLink,
} from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { NoTeamSeasons } from '@/components/no-team-seasons';
import { RevisionList } from '@/components/revision';
import { SynchronizeButton } from '@/components/synchronize-button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useImportPolling } from '@/hooks/use-import-polling';
import { index } from '@/routes/fixtures';
import { index as imports } from '@/routes/imports';
import type {
    FixtureBadge,
    FixtureList,
    FixtureListItem,
    FixtureListPeriod,
    ImportFailure,
} from '@/types';

type Props = {
    fixtureList: FixtureList | null;
    isImportRunning: boolean;
};

export default function Fixtures({ fixtureList, isImportRunning }: Props) {
    useImportPolling(isImportRunning);

    return (
        <>
            <Head title="Rozpis zápasů" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Rozpis zápasů"
                        description={freshness(fixtureList, isImportRunning)}
                    />
                    {/* A never imported fixture list offers the button in its empty state instead. */}
                    {fixtureList?.isImported && (
                        <SynchronizeButton isImportRunning={isImportRunning} />
                    )}
                </div>
                {fixtureList === null ? (
                    <NoTeamSeasons />
                ) : (
                    <>
                        {fixtureList.lastImportFailure !== null && (
                            <ImportFailureAlert
                                failure={fixtureList.lastImportFailure}
                            />
                        )}
                        {fixtureList.isImported ? (
                            <ImportedFixtureList fixtureList={fixtureList} />
                        ) : (
                            <NeverImported isImportRunning={isImportRunning} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function freshness(
    fixtureList: FixtureList | null,
    isImportRunning: boolean,
): string {
    if (fixtureList !== null && isImportRunning) {
        return 'Stahuji rozpis…';
    }

    return (
        fixtureList?.lastImportFinished ??
        'Rozpis tak, jak je uložený v aplikaci, pro srovnání s ceskyflorbal.cz.'
    );
}

Fixtures.layout = {
    breadcrumbs: [
        {
            title: 'Rozpis zápasů',
            href: index(),
        },
    ],
};

function ImportFailureAlert({ failure }: { failure: ImportFailure }) {
    return (
        <Alert variant="destructive">
            <AlertTriangle />
            <AlertTitle>
                {failure.status === 'error'
                    ? 'Poslední import rozpisu selhal'
                    : 'Poslední import rozpisu byl přerušen'}
            </AlertTitle>
            <AlertDescription>
                {failure.reason !== null && (
                    <p className="break-words">{failure.reason}</p>
                )}
                <p>
                    Uložený rozpis zůstal beze změny.{' '}
                    <Link
                        href={imports()}
                        className="font-medium text-foreground underline underline-offset-4"
                    >
                        Zobrazit importy
                    </Link>
                </p>
            </AlertDescription>
        </Alert>
    );
}

function NeverImported({ isImportRunning }: { isImportRunning: boolean }) {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <DownloadCloud />
                </EmptyMedia>
                <EmptyTitle>Rozpis zatím nebyl stažen</EmptyTitle>
                <EmptyDescription>
                    Spusťte import a stáhněte rozpis zápasů z ceskyflorbal.cz.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <SynchronizeButton isImportRunning={isImportRunning} />
            </EmptyContent>
        </Empty>
    );
}

function ImportedFixtureList({ fixtureList }: { fixtureList: FixtureList }) {
    const switchTo = (period: string) => {
        router.get(
            index.url(period === 'season' ? { query: { period } } : {}),
            {},
            { preserveScroll: true },
        );
    };

    // Only the selected period's fixtures are loaded, so only its panel is rendered.
    return (
        <Tabs
            value={fixtureList.period}
            onValueChange={switchTo}
            className="gap-4"
        >
            <div className="flex flex-wrap items-center justify-between gap-2">
                <PeriodTabs fixtureList={fixtureList} />
                <Button variant="outline" asChild>
                    <a
                        href={fixtureList.sourceUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        Otevřít na ceskyflorbal.cz
                        <ExternalLink />
                    </a>
                </Button>
            </div>
            <TabsContent value={fixtureList.period}>
                {fixtureList.fixtures.length === 0 ? (
                    <NoFixtures period={fixtureList.period} />
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {fixtureList.fixtures.map((fixture) => (
                            <FixtureRow key={fixture.id} fixture={fixture} />
                        ))}
                    </ul>
                )}
            </TabsContent>
        </Tabs>
    );
}

function PeriodTabs({ fixtureList }: { fixtureList: FixtureList }) {
    return (
        <TabsList>
            <TabsTrigger value="upcoming">
                Nadcházející
                <span className="text-muted-foreground tabular-nums">
                    {fixtureList.upcomingCount}
                </span>
            </TabsTrigger>
            <TabsTrigger value="season">
                Celá sezona
                <span className="text-muted-foreground tabular-nums">
                    {fixtureList.seasonCount}
                </span>
            </TabsTrigger>
        </TabsList>
    );
}

function NoFixtures({ period }: { period: FixtureListPeriod }) {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <CalendarOff />
                </EmptyMedia>
                <EmptyTitle>
                    {period === 'upcoming'
                        ? 'Žádné nadcházející zápasy'
                        : 'Rozpis je prázdný'}
                </EmptyTitle>
                {period === 'upcoming' && (
                    <EmptyDescription>
                        Odehrané zápasy najdete v celé sezoně.
                    </EmptyDescription>
                )}
            </EmptyHeader>
        </Empty>
    );
}

/**
 * One fixture in the column order of the federation's fixture list (date · round · home · score or time · away · venue), so the two pages read side by side line by line; a phone stacks the same cells. A revised fixture's "Změněno" badge opens its revisions in a panel under the row, so the row keeps one line however many revisions it has.
 */
function FixtureRow({ fixture }: { fixture: FixtureListItem }) {
    const [isExpanded, setIsExpanded] = useState(false);
    const isRevised = fixture.revisions.length > 0;

    return (
        <Collapsible open={isExpanded} onOpenChange={setIsExpanded} asChild>
            <li
                className={cn(
                    'grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-x-3 gap-y-1.5 px-3 py-2.5 text-sm',
                    'xl:grid-cols-[6rem_4.5rem_minmax(0,1fr)_4.5rem_minmax(0,1fr)_minmax(0,1fr)_12rem]',
                    isRevised && 'bg-violet-50/60 dark:bg-violet-950/30',
                )}
            >
                {/* The day and round share the top line on a phone and become their own columns on a wide screen. */}
                <div className="col-span-3 flex gap-1.5 text-muted-foreground xl:contents">
                    <span className="font-medium text-foreground tabular-nums">
                        {fixture.day}
                    </span>
                    {fixture.round !== null && (
                        <span aria-hidden className="xl:hidden">
                            ·
                        </span>
                    )}
                    <span>{fixture.round}</span>
                </div>
                <FixtureTeam
                    name={fixture.homeTeam}
                    isOurTeam={fixture.isHome}
                    isCancelled={fixture.status === 'cancelled'}
                    className="text-right"
                />
                {/* Tall enough for the score badge, so rows keep one height whether or not they show badges. */}
                <div className="flex min-h-7 items-center justify-center">
                    <FixtureScoreOrTime fixture={fixture} />
                </div>
                <FixtureTeam
                    name={fixture.awayTeam}
                    isOurTeam={!fixture.isHome}
                    isCancelled={fixture.status === 'cancelled'}
                />
                {/* On a phone an unknown venue would be a line holding only the dash. */}
                <div
                    className={cn(
                        'col-span-3 break-words text-muted-foreground xl:col-span-1',
                        fixture.venue === null && 'hidden xl:block',
                    )}
                >
                    {fixture.venue ?? '–'}
                </div>
                <div className="col-span-3 flex flex-wrap items-center gap-1.5 empty:hidden xl:col-span-1 xl:empty:block">
                    {fixture.badges.map((badge) =>
                        badge.kind === 'revised' ? (
                            <RevisionsToggle
                                key={badge.kind}
                                badge={badge}
                                revisionsCount={fixture.revisions.length}
                                isExpanded={isExpanded}
                            />
                        ) : (
                            <FixtureBadgeLabel key={badge.kind} badge={badge} />
                        ),
                    )}
                </div>
                {isRevised && (
                    <CollapsibleContent
                        data-test="fixture-revisions"
                        className="col-span-full mt-1 rounded-md border border-violet-200 bg-background p-3 dark:border-violet-900"
                    >
                        <RevisionList revisions={fixture.revisions} />
                    </CollapsibleContent>
                )}
            </li>
        </Collapsible>
    );
}

function FixtureTeam({
    name,
    isOurTeam,
    isCancelled,
    className,
}: {
    name: string;
    isOurTeam: boolean;
    isCancelled: boolean;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'break-words',
                isOurTeam && 'font-semibold',
                isCancelled && 'text-muted-foreground line-through',
                className,
            )}
        >
            {name}
        </span>
    );
}

function FixtureScoreOrTime({ fixture }: { fixture: FixtureListItem }) {
    if (fixture.score !== null) {
        return (
            <Badge
                variant="secondary"
                className="text-sm font-semibold tabular-nums"
            >
                {fixture.score}
            </Badge>
        );
    }

    if (fixture.time === null) {
        return <Badge variant="outline">TBD</Badge>;
    }

    return <span className="tabular-nums">{fixture.time}</span>;
}

const badgeClassNames: Partial<Record<FixtureBadge['kind'], string>> = {
    rescheduled:
        'border-sky-600/30 bg-sky-50 text-sky-700 dark:border-sky-400/30 dark:bg-sky-950 dark:text-sky-300',
    postponed:
        'border-amber-600/30 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-950 dark:text-amber-300',
    win: 'border-emerald-600/30 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-950 dark:text-emerald-300',
    loss: 'border-rose-600/30 bg-rose-50 text-rose-700 dark:border-rose-400/30 dark:bg-rose-950 dark:text-rose-300',
    revised:
        'border-violet-600/30 bg-violet-50 text-violet-700 dark:border-violet-400/30 dark:bg-violet-950 dark:text-violet-300',
};

function FixtureBadgeLabel({ badge }: { badge: FixtureBadge }) {
    if (badge.kind === 'cancelled') {
        return <Badge variant="destructive">{badge.label}</Badge>;
    }

    if (badge.kind === 'draw' || badge.kind === 'finished') {
        return <Badge variant="secondary">{badge.label}</Badge>;
    }

    return (
        <Badge variant="outline" className={badgeClassNames[badge.kind]}>
            {badge.label}
        </Badge>
    );
}

function RevisionsToggle({
    badge,
    revisionsCount,
    isExpanded,
}: {
    badge: FixtureBadge;
    revisionsCount: number;
    isExpanded: boolean;
}) {
    return (
        <CollapsibleTrigger asChild>
            <Badge
                asChild
                variant="outline"
                className={cn(
                    badgeClassNames.revised,
                    'cursor-pointer hover:bg-violet-100 dark:hover:bg-violet-900',
                )}
            >
                <button type="button" data-test="toggle-revisions">
                    {badge.label}
                    <span className="tabular-nums opacity-70">
                        {revisionsCount}
                    </span>
                    <ChevronDown
                        aria-hidden
                        className={cn(
                            'transition-transform',
                            isExpanded && 'rotate-180',
                        )}
                    />
                </button>
            </Badge>
        </CollapsibleTrigger>
    );
}
