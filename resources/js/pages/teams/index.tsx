import { Head, router, usePage } from '@inertiajs/react';
import { Copy, ExternalLink, Globe, MoreHorizontal, Users } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { ImportStatusBadge } from '@/components/import-status-badge';
import { NoTeamSeasons } from '@/components/no-team-seasons';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Empty,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useClipboard } from '@/hooks/use-clipboard';
import { index, update } from '@/routes/teams';
import type { TeamSeasonLastImport, TeamSeasonListItem } from '@/types';

type Props = {
    teamSeasons: TeamSeasonListItem[] | null;
};

export default function Teams({ teamSeasons }: Props) {
    const { adminSelection } = usePage().props;
    const season = adminSelection?.season ?? null;

    return (
        <>
            <Head title="Týmy" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Týmy"
                        description={
                            season === null
                                ? undefined
                                : `Týmy sezony ${season.name}. Automatický import stahuje rozpis každé 4 hodiny.`
                        }
                    />
                </div>
                {teamSeasons === null || season === null ? (
                    <NoTeamSeasons />
                ) : teamSeasons.length === 0 ? (
                    <NoTeamsInSeason seasonName={season.name} />
                ) : (
                    <TeamSeasonTable teamSeasons={teamSeasons} />
                )}
            </div>
        </>
    );
}

Teams.layout = {
    breadcrumbs: [
        {
            title: 'Týmy',
            href: index(),
        },
    ],
};

function NoTeamsInSeason({ seasonName }: { seasonName: string }) {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Users />
                </EmptyMedia>
                <EmptyTitle>
                    V sezoně {seasonName} zatím nejsou žádné týmy
                </EmptyTitle>
            </EmptyHeader>
        </Empty>
    );
}

function TeamSeasonTable({
    teamSeasons,
}: {
    teamSeasons: TeamSeasonListItem[];
}) {
    return (
        <div className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Tým</TableHead>
                        <TableHead className="hidden sm:table-cell">
                            Soutěž
                        </TableHead>
                        <TableHead className="hidden sm:table-cell">
                            Slug
                        </TableHead>
                        <TableHead className="hidden sm:table-cell">
                            Poslední import
                        </TableHead>
                        <TableHead>Automaticky</TableHead>
                        <TableHead>
                            <span className="sr-only">Akce</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {teamSeasons.map((teamSeason) => (
                        <TableRow key={teamSeason.id}>
                            <TableCell className="whitespace-normal">
                                <div className="font-medium">
                                    {teamSeason.name}
                                </div>
                                <div className="mt-1 text-xs text-muted-foreground sm:hidden">
                                    <LastImport
                                        lastImport={teamSeason.lastImport}
                                    />
                                </div>
                            </TableCell>
                            <TableCell className="hidden whitespace-normal sm:table-cell">
                                {teamSeason.competition ?? (
                                    <span className="text-muted-foreground">
                                        –
                                    </span>
                                )}
                            </TableCell>
                            <TableCell className="hidden font-mono text-xs sm:table-cell">
                                {teamSeason.slug}
                            </TableCell>
                            <TableCell className="hidden sm:table-cell">
                                <LastImport
                                    lastImport={teamSeason.lastImport}
                                />
                            </TableCell>
                            <TableCell>
                                <AutoImportSwitch teamSeason={teamSeason} />
                            </TableCell>
                            <TableCell className="text-right">
                                <TeamSeasonActions teamSeason={teamSeason} />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function LastImport({
    lastImport,
}: {
    lastImport: TeamSeasonLastImport | null;
}) {
    if (lastImport === null) {
        return <span className="text-muted-foreground">Zatím neproběhl</span>;
    }

    return (
        <span className="inline-flex flex-wrap items-center gap-2">
            <span className="tabular-nums">{lastImport.startedAt}</span>
            {lastImport.failure !== null && (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <span tabIndex={0}>
                            <ImportStatusBadge
                                status={lastImport.failure.status}
                                label={lastImport.failure.statusLabel}
                            />
                        </span>
                    </TooltipTrigger>
                    {lastImport.failure.reason !== null && (
                        <TooltipContent className="max-w-xs">
                            {lastImport.failure.reason}
                        </TooltipContent>
                    )}
                </Tooltip>
            )}
        </span>
    );
}

function AutoImportSwitch({ teamSeason }: { teamSeason: TeamSeasonListItem }) {
    function toggle(isEnabled: boolean): void {
        // The switch flips at once; Inertia puts it back if the server refuses the change.
        router
            .optimistic<Props>((props) => ({
                teamSeasons:
                    props.teamSeasons?.map((item) =>
                        item.id === teamSeason.id
                            ? { ...item, autoImportEnabled: isEnabled }
                            : item,
                    ) ?? null,
            }))
            .patch(
                update.url(teamSeason.id),
                { auto_import_enabled: isEnabled },
                { preserveScroll: true },
            );
    }

    return (
        <Switch
            checked={teamSeason.autoImportEnabled}
            onCheckedChange={toggle}
            aria-label={`Automatický import týmu ${teamSeason.name}`}
            data-test="auto-import-switch"
        />
    );
}

function TeamSeasonActions({ teamSeason }: { teamSeason: TeamSeasonListItem }) {
    const [, copy] = useClipboard();
    const [isAddressShown, setIsAddressShown] = useState(false);

    // Some browsers block the clipboard, so the address is shown selected for the administrator to copy by hand.
    async function copyCalendarUrl(): Promise<void> {
        if (await copy(teamSeason.calendarUrl)) {
            toast.success('Adresa kalendáře je zkopírovaná.');
        } else {
            setIsAddressShown(true);
        }
    }

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        variant="ghost"
                        size="icon"
                        data-test="team-season-actions"
                    >
                        <MoreHorizontal />
                        <span className="sr-only">
                            Akce týmu {teamSeason.name}
                        </span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem onSelect={() => void copyCalendarUrl()}>
                        <Copy />
                        Kopírovat adresu kalendáře
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <a
                            href={teamSeason.publicPageUrl}
                            data-test="open-public-page"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Globe />
                            Otevřít veřejnou stránku
                        </a>
                    </DropdownMenuItem>
                    <DropdownMenuItem asChild>
                        <a
                            href={teamSeason.sourceUrl}
                            data-test="open-source"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink />
                            Otevřít rozpis na ceskyflorbal.cz
                        </a>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
            <Dialog open={isAddressShown} onOpenChange={setIsAddressShown}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Adresa kalendáře</DialogTitle>
                        <DialogDescription>
                            Prohlížeč nedovolil adresu zkopírovat. Zkopírujte ji
                            ručně.
                        </DialogDescription>
                    </DialogHeader>
                    <Input
                        readOnly
                        autoFocus
                        data-test="calendar-address"
                        aria-label={`Adresa kalendáře týmu ${teamSeason.name}`}
                        value={teamSeason.calendarUrl}
                        onFocus={(event) => event.currentTarget.select()}
                    />
                </DialogContent>
            </Dialog>
        </>
    );
}
