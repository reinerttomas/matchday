import { Form, Head, router, usePage } from '@inertiajs/react';
import {
    Copy,
    ExternalLink,
    Globe,
    MoreHorizontal,
    Pencil,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { AddTeamDialog } from '@/components/add-team-dialog';
import Heading from '@/components/heading';
import { ImportStatusBadge } from '@/components/import-status-badge';
import InputError from '@/components/input-error';
import { NoTeamSeasons } from '@/components/no-team-seasons';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
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
import { update as updateName } from '@/routes/teams/name';
import type {
    CarryOverTeam,
    TeamSeasonLastImport,
    TeamSeasonListItem,
} from '@/types';

type Props = {
    teamSeasons: TeamSeasonListItem[] | null;
    carryOverTeams: CarryOverTeam[];
    calendarUrlTemplate: string;
};

export default function Teams({
    teamSeasons,
    carryOverTeams,
    calendarUrlTemplate,
}: Props) {
    const { adminSelection } = usePage().props;
    const season = adminSelection?.season ?? null;

    return (
        <>
            <Head title="Týmy" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Týmy"
                        description={
                            season === null
                                ? undefined
                                : `Týmy sezony ${season.name}. Automatický import stahuje rozpis každé 4 hodiny.`
                        }
                    />
                    {season !== null && (
                        <AddTeamDialog
                            seasonName={season.name}
                            carryOverTeams={carryOverTeams}
                            calendarUrlTemplate={calendarUrlTemplate}
                        />
                    )}
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
    const [isRenaming, setIsRenaming] = useState(false);

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
                    <DropdownMenuItem
                        onSelect={() => setIsRenaming(true)}
                        data-test="rename-team"
                    >
                        <Pencil />
                        Přejmenovat tým
                    </DropdownMenuItem>
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
                    <DropdownMenuItem asChild>
                        <a
                            href={teamSeason.overviewUrl}
                            data-test="open-overview"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink />
                            Přehled na Český florbal
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
            <Dialog open={isRenaming} onOpenChange={setIsRenaming}>
                <DialogContent>
                    <RenameTeamForm
                        teamSeason={teamSeason}
                        onSuccess={() => setIsRenaming(false)}
                    />
                </DialogContent>
            </Dialog>
        </>
    );
}

function RenameTeamForm({
    teamSeason,
    onSuccess,
}: {
    teamSeason: TeamSeasonListItem;
    onSuccess: () => void;
}) {
    return (
        <Form
            {...updateName.form(teamSeason.teamId)}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
            className="grid gap-4 text-left"
        >
            {({ processing, errors }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>
                            Přejmenovat tým {teamSeason.name}
                        </DialogTitle>
                        <DialogDescription>
                            Nový název se ukáže ve všech sezonách týmu, v
                            kalendáři i na veřejné stránce.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor={`team-${teamSeason.teamId}-name`}>
                            Název týmu
                        </Label>
                        <Input
                            id={`team-${teamSeason.teamId}-name`}
                            name="name"
                            required
                            autoFocus
                            autoComplete="off"
                            defaultValue={teamSeason.name}
                            aria-invalid={errors.name !== undefined}
                            aria-describedby={`team-${teamSeason.teamId}-calendar-url`}
                        />
                        <InputError message={errors.name} />
                        <p
                            id={`team-${teamSeason.teamId}-calendar-url`}
                            className="text-sm text-muted-foreground"
                        >
                            Adresa kalendáře zůstává stejná, takže hráči nemusí
                            kalendář přidávat znovu:{' '}
                            <span className="font-mono text-xs break-all text-foreground">
                                {teamSeason.calendarUrl}
                            </span>
                        </p>
                    </div>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Zrušit
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            disabled={processing}
                            data-test="update-team-name"
                        >
                            {processing && <Spinner />}
                            Přejmenovat tým
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}
