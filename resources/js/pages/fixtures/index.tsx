import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarOff,
    DownloadCloud,
    MapPin,
} from 'lucide-react';
import { Fragment } from 'react';
import Heading from '@/components/heading';
import { Matchup } from '@/components/matchup';
import { NoTeamSeasons } from '@/components/no-team-seasons';
import { SynchronizeButton } from '@/components/synchronize-button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useImportPolling } from '@/hooks/use-import-polling';
import { cn } from '@/lib/utils';
import { index } from '@/routes/fixtures';
import { index as imports } from '@/routes/imports';
import type {
    FixtureBadge,
    FixtureList,
    FixtureListItem,
    FixtureListMonth,
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
            <PeriodTabs fixtureList={fixtureList} />
            <TabsContent value={fixtureList.period}>
                {fixtureList.months.length === 0 ? (
                    <NoFixtures period={fixtureList.period} />
                ) : (
                    <>
                        <FixtureTable months={fixtureList.months} />
                        <FixtureCards months={fixtureList.months} />
                    </>
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

function FixtureTable({ months }: { months: FixtureListMonth[] }) {
    return (
        <div className="hidden rounded-lg border lg:block">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Den</TableHead>
                        <TableHead>Čas</TableHead>
                        <TableHead>Zápas</TableHead>
                        <TableHead>Hala</TableHead>
                        <TableHead>Stav</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {months.map((month) => (
                        <Fragment key={month.month}>
                            <TableRow className="bg-muted/50 hover:bg-muted/50">
                                <TableHead
                                    colSpan={5}
                                    scope="colgroup"
                                    className="font-semibold"
                                >
                                    {month.heading}
                                </TableHead>
                            </TableRow>
                            {month.fixtures.map((fixture) => (
                                <TableRow key={fixture.id}>
                                    <TableCell className="font-medium tabular-nums">
                                        {fixture.day}
                                    </TableCell>
                                    <TableCell>
                                        <FixtureTime time={fixture.time} />
                                    </TableCell>
                                    <TableCell className="whitespace-normal">
                                        <FixtureMatchup fixture={fixture} />
                                    </TableCell>
                                    <TableCell className="whitespace-normal text-muted-foreground">
                                        {fixture.venue ?? '–'}
                                    </TableCell>
                                    <TableCell>
                                        <FixtureState fixture={fixture} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </Fragment>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function FixtureCards({ months }: { months: FixtureListMonth[] }) {
    return (
        <div className="flex flex-col gap-6 lg:hidden">
            {months.map((month) => (
                <section
                    key={month.month}
                    aria-labelledby={`month-${month.month}`}
                >
                    <h3
                        id={`month-${month.month}`}
                        className="mb-2 font-semibold"
                    >
                        {month.heading}
                    </h3>
                    <Card className="gap-0 py-0">
                        <ul className="divide-y">
                            {month.fixtures.map((fixture) => (
                                <FixtureCardItem
                                    key={fixture.id}
                                    fixture={fixture}
                                />
                            ))}
                        </ul>
                    </Card>
                </section>
            ))}
        </div>
    );
}

function FixtureCardItem({ fixture }: { fixture: FixtureListItem }) {
    return (
        <li className="flex flex-col gap-1.5 px-3 py-3">
            <div className="flex items-center gap-2 text-sm font-medium tabular-nums">
                <span>{fixture.day}</span>
                <FixtureTime time={fixture.time} />
            </div>
            <FixtureMatchup fixture={fixture} />
            {fixture.venue !== null && (
                <span className="inline-flex min-w-0 items-center gap-1 text-sm text-muted-foreground">
                    <MapPin className="size-3.5 shrink-0" aria-hidden />
                    <span className="break-words">{fixture.venue}</span>
                </span>
            )}
            <FixtureState fixture={fixture} />
            {/* Tooltips don't open on a tap, so a phone lists the revisions in place. */}
            {fixture.revisions.length > 0 && (
                <ul className="space-y-0.5 text-xs break-words text-muted-foreground">
                    {fixture.revisions.map((revision, position) => (
                        <li key={position}>{revision}</li>
                    ))}
                </ul>
            )}
        </li>
    );
}

function FixtureTime({ time }: { time: string | null }) {
    if (time === null) {
        return <Badge variant="outline">TBD</Badge>;
    }

    return <span className="tabular-nums">{time}</span>;
}

function FixtureMatchup({ fixture }: { fixture: FixtureListItem }) {
    return (
        <Matchup
            parts={fixture.matchup}
            className={cn(
                fixture.status === 'cancelled' &&
                    'text-muted-foreground line-through',
            )}
        />
    );
}

function FixtureState({ fixture }: { fixture: FixtureListItem }) {
    if (fixture.badges.length === 0 && fixture.score === null) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-1.5">
            {fixture.badges.map((badge) =>
                badge.kind === 'revised' ? (
                    <RevisedBadge
                        key={badge.kind}
                        badge={badge}
                        revisions={fixture.revisions}
                    />
                ) : (
                    <FixtureBadgeLabel key={badge.kind} badge={badge} />
                ),
            )}
            {fixture.score !== null && (
                <span className="font-semibold tabular-nums">
                    {fixture.score}
                </span>
            )}
        </div>
    );
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

function RevisedBadge({
    badge,
    revisions,
}: {
    badge: FixtureBadge;
    revisions: string[];
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Badge
                    variant="outline"
                    tabIndex={0}
                    className={cn('cursor-help', badgeClassNames.revised)}
                >
                    {badge.label}
                </Badge>
            </TooltipTrigger>
            <TooltipContent>
                <ul className="space-y-0.5">
                    {revisions.map((revision, position) => (
                        <li key={position}>{revision}</li>
                    ))}
                </ul>
            </TooltipContent>
        </Tooltip>
    );
}
