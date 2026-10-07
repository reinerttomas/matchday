import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DownloadCloud } from 'lucide-react';
import Heading from '@/components/heading';
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
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useImportPolling } from '@/hooks/use-import-polling';
import { index } from '@/routes/imports';
import type { ImportHistoryItem, Paginated } from '@/types';

type Props = {
    imports: Paginated<ImportHistoryItem> | null;
    isImportRunning: boolean;
};

export default function Imports({ imports, isImportRunning }: Props) {
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
                    {imports !== null && (
                        <SynchronizeButton isImportRunning={isImportRunning} />
                    )}
                </div>
                {imports === null ? (
                    <NoTeamSeasons />
                ) : imports.data.length === 0 ? (
                    <NoImports />
                ) : (
                    <>
                        <ImportTable imports={imports.data} />
                        <ImportPagination imports={imports} />
                    </>
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

function ImportTable({ imports }: { imports: ImportHistoryItem[] }) {
    return (
        <div className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Začátek</TableHead>
                        <TableHead>Výsledek</TableHead>
                        <TableHead className="hidden sm:table-cell">
                            Trvání
                        </TableHead>
                        <TableHead className="text-right">Zápasů</TableHead>
                        <TableHead className="text-right">Změn</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {imports.map((item) => (
                        <TableRow key={item.id}>
                            <TableCell className="align-top">
                                <div className="font-medium tabular-nums">
                                    {item.startedAt}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    {item.trigger === 'manual' &&
                                        item.triggerLabel}
                                    {item.duration !== null && (
                                        <span className="sm:hidden">
                                            {item.trigger === 'manual' && ' · '}
                                            {item.duration}
                                        </span>
                                    )}
                                </div>
                            </TableCell>
                            <TableCell className="align-top whitespace-normal">
                                <ImportStatusBadge item={item} />
                                {item.reason !== null && (
                                    <p className="mt-1 max-w-xs text-xs break-words text-muted-foreground">
                                        {item.reason}
                                    </p>
                                )}
                            </TableCell>
                            <TableCell className="hidden align-top tabular-nums sm:table-cell">
                                {item.duration ?? '–'}
                            </TableCell>
                            <TableCell className="text-right align-top tabular-nums">
                                {item.fixturesFound ?? '–'}
                            </TableCell>
                            <TableCell className="text-right align-top">
                                {item.revisionsCount > 0 ? (
                                    <Badge className="tabular-nums">
                                        {item.revisionsCount}
                                    </Badge>
                                ) : (
                                    <span className="text-muted-foreground tabular-nums">
                                        0
                                    </span>
                                )}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function ImportStatusBadge({ item }: { item: ImportHistoryItem }) {
    switch (item.status) {
        case 'ok':
            return (
                <Badge
                    variant="outline"
                    className="border-emerald-600/30 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    {item.statusLabel}
                </Badge>
            );
        case 'error':
            return <Badge variant="destructive">{item.statusLabel}</Badge>;
        case 'aborted':
            return (
                <Badge
                    variant="outline"
                    className="border-amber-600/30 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-950 dark:text-amber-300"
                >
                    {item.statusLabel}
                </Badge>
            );
        case 'running':
            return (
                <Badge variant="secondary">
                    <Spinner className="size-3" />
                    {item.statusLabel}
                </Badge>
            );
    }
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
