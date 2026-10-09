import { Head, router } from '@inertiajs/react';
import { cn } from 'cn';
import { CheckCheck, ChevronRight, History } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { ChangeSummaryDialog } from '@/components/change-summary-dialog';
import Heading from '@/components/heading';
import { Matchup } from '@/components/matchup';
import { NoTeamSeasons } from '@/components/no-team-seasons';
import { Revision } from '@/components/revision';
import { Badge } from '@/components/ui/badge';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { index } from '@/routes/changes';
import type {
    AddedFixture,
    InitialImport,
    RevisedFixture,
    RevisingImport,
    RevisionHistory,
    RevisionHistoryFilter,
} from '@/types';

type Props = {
    revisionHistory: RevisionHistory | null;
};

export default function Changes({ revisionHistory }: Props) {
    return (
        <>
            <Head title="Změny" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Změny"
                    description="Co se v rozpisu změnilo, kdy, a jestli už to tým ví."
                />
                {revisionHistory === null ? (
                    <NoTeamSeasons />
                ) : revisionHistory.allCount === 0 ? (
                    <UnrevisedFixtureList
                        initialImport={revisionHistory.initialImport}
                    />
                ) : (
                    <FilteredRevisionHistory
                        revisionHistory={revisionHistory}
                    />
                )}
            </div>
        </>
    );
}

Changes.layout = {
    breadcrumbs: [
        {
            title: 'Změny',
            href: index(),
        },
    ],
};

function UnrevisedFixtureList({
    initialImport,
}: {
    initialImport: InitialImport | null;
}) {
    return (
        <>
            <NoRevisions isImported={initialImport !== null} />
            {initialImport !== null && (
                <ul className="overflow-hidden rounded-lg border">
                    <InitialImportRows initialImport={initialImport} />
                </ul>
            )}
        </>
    );
}

function NoRevisions({ isImported }: { isImported: boolean }) {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <History />
                </EmptyMedia>
                <EmptyTitle>
                    {isImported
                        ? 'Rozpis se od importu nezměnil'
                        : 'Rozpis zatím nebyl stažen'}
                </EmptyTitle>
                <EmptyDescription>
                    {isImported
                        ? 'Jakmile import najde v rozpisu změnu, objeví se tady.'
                        : 'Změny se tu objeví po prvním importu rozpisu.'}
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}

function FilteredRevisionHistory({
    revisionHistory,
}: {
    revisionHistory: RevisionHistory;
}) {
    // Without a filter the page opens on whatever there is to send, so a tab always names its filter.
    const switchTo = (filter: string) => {
        router.get(
            index.url({ query: { filter } }),
            {},
            { preserveScroll: true },
        );
    };

    // Only the selected filter's imports are loaded, so only its panel is rendered.
    return (
        <Tabs
            value={revisionHistory.filter}
            onValueChange={switchTo}
            className="gap-4"
        >
            <TabsList>
                <FilterTab value="unsent" count={revisionHistory.unsentCount}>
                    K odeslání
                </FilterTab>
                <FilterTab value="all" count={revisionHistory.allCount}>
                    Vše
                </FilterTab>
            </TabsList>
            <TabsContent value={revisionHistory.filter}>
                {revisionHistory.imports.length === 0 &&
                revisionHistory.initialImport === null ? (
                    <NothingToSend />
                ) : (
                    <ul className="divide-y overflow-hidden rounded-lg border">
                        {revisionHistory.imports.map((revisingImport) => (
                            <RevisingImportRows
                                key={revisingImport.id}
                                revisingImport={revisingImport}
                            />
                        ))}
                        {revisionHistory.initialImport !== null && (
                            <InitialImportRows
                                initialImport={revisionHistory.initialImport}
                            />
                        )}
                    </ul>
                )}
            </TabsContent>
        </Tabs>
    );
}

function FilterTab({
    value,
    count,
    children,
}: {
    value: RevisionHistoryFilter;
    count: number;
    children: ReactNode;
}) {
    return (
        <TabsTrigger value={value}>
            {children}
            <span className="text-muted-foreground tabular-nums">{count}</span>
        </TabsTrigger>
    );
}

function NothingToSend() {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <CheckCheck />
                </EmptyMedia>
                <EmptyTitle>Vše je odesláno</EmptyTitle>
                <EmptyDescription>Tým ví o všech změnách.</EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}

/**
 * An import as a muted header row followed by one row per fixture it revised, tinted like a revised fixture on Rozpis zápasů until the team has been told, so what is left to send stands out.
 */
function RevisingImportRows({
    revisingImport,
}: {
    revisingImport: RevisingImport;
}) {
    const { notified } = revisingImport;
    const isUnsent = notified === null;

    return (
        <li>
            <div className="flex min-h-12 items-center gap-3 bg-muted/40 px-3 py-2 text-sm">
                <StartedAt
                    startedOn={revisingImport.startedOn}
                    startedAt={revisingImport.startedAt}
                />
                <Badge
                    variant="outline"
                    className="border-violet-600/30 bg-violet-50 text-violet-700 dark:border-violet-400/30 dark:bg-violet-950 dark:text-violet-300"
                >
                    {revisingImport.revisionsLabel}
                </Badge>
                <div className="ml-auto">
                    {isUnsent ? (
                        <ChangeSummaryDialog importId={revisingImport.id} />
                    ) : (
                        <SentBadge notified={notified} />
                    )}
                </div>
            </div>
            <ul className="divide-y border-t">
                {revisingImport.fixtures.map((fixture) => (
                    <RevisedFixtureRow
                        key={fixture.id}
                        fixture={fixture}
                        isUnsent={isUnsent}
                    />
                ))}
            </ul>
        </li>
    );
}

/**
 * The date sits above the time on a phone and beside it on a wider screen, as on Importy.
 */
function StartedAt({
    startedOn,
    startedAt,
}: {
    startedOn: string;
    startedAt: string;
}) {
    return (
        <span className="flex shrink-0 flex-col text-xs whitespace-nowrap tabular-nums sm:flex-row sm:gap-3 sm:text-sm">
            <span className="text-muted-foreground">{startedOn}</span>
            <span className="font-medium">{startedAt}</span>
        </span>
    );
}

function SentBadge({ notified }: { notified: string }) {
    return (
        <Badge
            variant="outline"
            className="border-emerald-600/30 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-950 dark:text-emerald-300"
        >
            <CheckCheck />
            <span className="sm:hidden">Odesláno</span>
            <span className="hidden sm:inline">{notified}</span>
        </Badge>
    );
}

/**
 * The day, the sides and the revisions side by side on a wide screen; a phone keeps the day and sides on one line and wraps the revisions below.
 */
function RevisedFixtureRow({
    fixture,
    isUnsent,
}: {
    fixture: RevisedFixture;
    isUnsent: boolean;
}) {
    return (
        <li
            className={cn(
                'grid grid-cols-[5.5rem_minmax(0,1fr)] gap-x-3 gap-y-1 px-3 py-2.5 text-sm',
                'xl:grid-cols-[6rem_minmax(0,1fr)_minmax(0,1.4fr)]',
                isUnsent && 'bg-violet-50/60 dark:bg-violet-950/30',
            )}
        >
            <FixtureDayAndSides fixture={fixture} />
            <ul className="col-span-full flex flex-wrap gap-x-4 gap-y-0.5 text-xs xl:col-span-1 xl:text-sm">
                {fixture.revisions.map((parts, position) => (
                    <li key={position} className="break-words">
                        <Revision parts={parts} />
                    </li>
                ))}
            </ul>
        </li>
    );
}

function FixtureDayAndSides({ fixture }: { fixture: AddedFixture }) {
    return (
        <>
            <span className="font-medium whitespace-nowrap tabular-nums">
                {fixture.day}
            </span>
            <Matchup parts={fixture.matchup} />
        </>
    );
}

/**
 * The initial import as a header row in the style of the others, collapsed, as it adds the whole fixture list and is never announced to the team.
 */
function InitialImportRows({
    initialImport,
}: {
    initialImport: InitialImport;
}) {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <Collapsible open={isOpen} onOpenChange={setIsOpen} asChild>
            <li>
                <CollapsibleTrigger className="flex min-h-12 w-full items-center gap-3 bg-muted/40 px-3 py-2 text-left text-sm outline-none hover:bg-muted/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:ring-inset">
                    <StartedAt
                        startedOn={initialImport.startedOn}
                        startedAt={initialImport.startedAt}
                    />
                    <span className="flex min-w-0 flex-col sm:flex-row sm:flex-wrap sm:gap-x-3">
                        <span>{initialImport.summary}</span>
                        <span className="text-xs text-muted-foreground sm:text-sm">
                            První import, týmu se neoznamuje
                        </span>
                    </span>
                    <ChevronRight
                        aria-hidden
                        className={cn(
                            'ml-auto size-4 shrink-0 text-muted-foreground transition-transform',
                            isOpen && 'rotate-90',
                        )}
                    />
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <ul className="divide-y border-t">
                        {initialImport.fixtures.map((fixture) => (
                            <li
                                key={fixture.id}
                                className="grid grid-cols-[5.5rem_minmax(0,1fr)] gap-x-3 px-3 py-2.5 text-sm xl:grid-cols-[6rem_minmax(0,1fr)]"
                            >
                                <FixtureDayAndSides fixture={fixture} />
                            </li>
                        ))}
                    </ul>
                </CollapsibleContent>
            </li>
        </Collapsible>
    );
}
