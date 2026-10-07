import { Head, Link, router } from '@inertiajs/react';
import { cn } from 'cn';
import type { ReactNode } from 'react';
import { CalendarClock, DownloadCloud, Hand, SearchX } from 'lucide-react';
import Heading from '@/components/heading';
import { ImportStatusBadge } from '@/components/import-status-badge';
import { NoTeamSeasons } from '@/components/no-team-seasons';
import { SynchronizeButton } from '@/components/synchronize-button';
import { Badge } from '@/components/ui/badge';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
} from '@/components/ui/pagination';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useImportPolling } from '@/hooks/use-import-polling';
import { index as changes } from '@/routes/changes';
import { index } from '@/routes/imports';
import type {
    ImportHistory,
    ImportHistoryFilter,
    ImportHistoryItem,
    Paginated,
} from '@/types';

type Props = {
    importHistory: ImportHistory | null;
    isImportRunning: boolean;
};

export default function Imports({ importHistory, isImportRunning }: Props) {
    useImportPolling(isImportRunning);

    return (
        <>
            <Head title="Importy" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Importy"
                        description="Rozpis se stahuje z ceskyflorbal.cz každé 4 hodiny."
                    />
                    {importHistory !== null && (
                        <SynchronizeButton isImportRunning={isImportRunning} />
                    )}
                </div>
                {importHistory === null ? (
                    <NoTeamSeasons />
                ) : importHistory.allCount === 0 ? (
                    <NoImports />
                ) : (
                    <FilteredImports importHistory={importHistory} />
                )}
            </div>
        </>
    );
}

Imports.layout = {
    breadcrumbs: [
        {
            title: 'Importy',
            href: index(),
        },
    ],
};

function NoImports() {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <DownloadCloud />
                </EmptyMedia>
                <EmptyTitle>Zatím žádné importy</EmptyTitle>
                <EmptyDescription>
                    První import proběhne při nejbližším stahování rozpisu, nebo
                    ho spusťte tlačítkem Synchronizovat.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}

function FilteredImports({ importHistory }: { importHistory: ImportHistory }) {
    const switchTo = (filter: string) => {
        router.get(
            index.url(filter === 'all' ? {} : { query: { filter } }),
            {},
            { preserveScroll: true },
        );
    };

    // Only the selected filter's imports are loaded, so only its panel is rendered.
    return (
        <Tabs
            value={importHistory.filter}
            onValueChange={switchTo}
            className="gap-4"
        >
            <TabsList>
                <FilterTab value="all" count={importHistory.allCount}>
                    Vše
                </FilterTab>
                <FilterTab value="revised" count={importHistory.revisedCount}>
                    Se změnami
                </FilterTab>
                <FilterTab value="failed" count={importHistory.failedCount}>
                    Selhané
                </FilterTab>
            </TabsList>
            <TabsContent value={importHistory.filter} className="space-y-4">
                {importHistory.imports.data.length === 0 ? (
                    <NoFilteredImports filter={importHistory.filter} />
                ) : (
                    <ul className="divide-y rounded-lg border">
                        {importHistory.imports.data.map((item) => (
                            <ImportRow key={item.id} item={item} />
                        ))}
                    </ul>
                )}
                <ImportPagination imports={importHistory.imports} />
            </TabsContent>
        </Tabs>
    );
}

function FilterTab({
    value,
    count,
    children,
}: {
    value: ImportHistoryFilter;
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

const noFilteredImportsTitles: Record<ImportHistoryFilter, string> = {
    all: 'Zatím žádné importy',
    revised: 'Žádné importy se změnami',
    failed: 'Žádné selhané importy',
};

function NoFilteredImports({ filter }: { filter: ImportHistoryFilter }) {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <SearchX />
                </EmptyMedia>
                <EmptyTitle>{noFilteredImportsTitles[filter]}</EmptyTitle>
            </EmptyHeader>
        </Empty>
    );
}

/**
 * One import per line on every screen, tinted like a revised fixture on Rozpis zápasů, so a failed or revising import stands out and a failure reason never makes its row taller than the others.
 */
function ImportRow({ item }: { item: ImportHistoryItem }) {
    return (
        <li
            className={cn(
                'grid grid-cols-[auto_auto_auto_minmax(0,1fr)_auto] items-center gap-x-3 px-3 py-2.5 text-sm',
                'xl:grid-cols-[6.5rem_3rem_1rem_6rem_minmax(0,1fr)_5.5rem_6rem]',
                item.status === 'error' && 'bg-rose-50/60 dark:bg-rose-950/30',
                item.status === 'aborted' &&
                    'bg-amber-50/60 dark:bg-amber-950/30',
                item.status === 'ok' &&
                    item.revisionsCount > 0 &&
                    'bg-violet-50/60 dark:bg-violet-950/30',
            )}
        >
            {/* The date sits above the time on a phone and gets its own column on a wide screen. */}
            <div className="flex flex-col text-xs tabular-nums xl:contents xl:text-sm">
                <span className="text-muted-foreground">{item.startedOn}</span>
                <span className="font-medium">{item.startedAt}</span>
            </div>
            <ImportTriggerIcon trigger={item.trigger} />
            <div>
                <ImportStatusBadge
                    status={item.status}
                    label={item.statusLabel}
                />
            </div>
            <ImportOutcome item={item} />
            <span className="hidden text-muted-foreground tabular-nums xl:block">
                {item.duration ?? '–'}
            </span>
            <div className="text-right">
                {item.revisionsCount > 0 && (
                    <Badge
                        asChild
                        variant="outline"
                        className="border-violet-600/30 bg-violet-50 text-violet-700 dark:border-violet-400/30 dark:bg-violet-950 dark:text-violet-300"
                    >
                        <Link href={changes()}>{item.revisionsLabel}</Link>
                    </Badge>
                )}
            </div>
        </li>
    );
}

function ImportTriggerIcon({
    trigger,
}: {
    trigger: ImportHistoryItem['trigger'];
}) {
    const label =
        trigger === 'manual' ? 'Spuštěno ručně' : 'Automaticky každé 4 hodiny';
    const Icon = trigger === 'manual' ? Hand : CalendarClock;

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span className="text-muted-foreground">
                    <Icon className="size-4" aria-label={label} role="img" />
                </span>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

/**
 * The reason of a failed import in place of the fixtures found, cut to one line.
 */
function ImportOutcome({ item }: { item: ImportHistoryItem }) {
    if (item.reason === null) {
        return (
            <span className="truncate text-muted-foreground tabular-nums">
                {item.fixturesFoundLabel ?? '–'}
            </span>
        );
    }

    return (
        <span
            className={cn(
                'truncate',
                item.status === 'error'
                    ? 'text-rose-700 dark:text-rose-300'
                    : 'text-amber-800 dark:text-amber-300',
            )}
        >
            {item.reason}
        </span>
    );
}

function ImportPagination({
    imports,
}: {
    imports: Paginated<ImportHistoryItem>;
}) {
    if (imports.last_page <= 1) {
        return null;
    }

    // The first and last links are Laravel's previous/next, which get Czech labels here instead.
    const pages = imports.links.slice(1, -1);

    return (
        <Pagination>
            <PaginationContent className="flex-wrap">
                <PaginationItem>
                    <PageLink url={imports.prev_page_url} size="default">
                        Předchozí
                    </PageLink>
                </PaginationItem>
                {pages.map((page, position) => (
                    <PaginationItem
                        key={`${page.label}-${position}`}
                        className="hidden sm:list-item"
                    >
                        {page.url === null ? (
                            <PaginationEllipsis />
                        ) : (
                            <PageLink url={page.url} isActive={page.active}>
                                {page.label}
                            </PageLink>
                        )}
                    </PaginationItem>
                ))}
                <PaginationItem>
                    <PageLink url={imports.next_page_url} size="default">
                        Další
                    </PageLink>
                </PaginationItem>
            </PaginationContent>
        </Pagination>
    );
}

function PageLink({
    url,
    isActive = false,
    size = 'icon',
    children,
}: {
    url: string | null;
    isActive?: boolean;
    size?: 'icon' | 'default';
    children: ReactNode;
}) {
    if (url === null) {
        return (
            <PaginationLink
                aria-disabled
                size={size}
                className="pointer-events-none opacity-50"
            >
                {children}
            </PaginationLink>
        );
    }

    return (
        <PaginationLink asChild isActive={isActive} size={size}>
            <Link href={url} preserveScroll>
                {children}
            </Link>
        </PaginationLink>
    );
}
