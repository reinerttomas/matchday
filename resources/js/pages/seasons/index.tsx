import { Form, Head, router } from '@inertiajs/react';
import { CalendarRange, Plus } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { index, store } from '@/routes/seasons';
import { store as markAsCurrent } from '@/routes/seasons/current';
import type { SeasonListItem } from '@/types';

type Props = {
    seasons: SeasonListItem[];
};

export default function Seasons({ seasons }: Props) {
    return (
        <>
            <Head title="Sezony" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Sezony"
                        description="Aktuální sezona určuje, co ukazují kalendáře a veřejné stránky týmů a které týmy se importují automaticky."
                    />
                    <CreateSeasonDialog />
                </div>
                {seasons.length === 0 ? (
                    <NoSeasons />
                ) : (
                    <SeasonTable seasons={seasons} />
                )}
            </div>
        </>
    );
}

Seasons.layout = {
    breadcrumbs: [
        {
            title: 'Sezony',
            href: index(),
        },
    ],
};

function NoSeasons() {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <CalendarRange />
                </EmptyMedia>
                <EmptyTitle>Zatím tu není žádná sezona</EmptyTitle>
                <EmptyDescription>
                    Vytvořte první sezonu a nastavte ji jako aktuální.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}

function CreateSeasonDialog() {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button className="self-start" data-test="create-season">
                    <Plus />
                    Nová sezona
                </Button>
            </DialogTrigger>
            <DialogContent>
                <Form
                    {...store.form()}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setIsOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Nová sezona</DialogTitle>
                                <DialogDescription>
                                    Nová sezona nebude aktuální, dokud ji tak
                                    nenastavíte. Kalendáře a automatické importy
                                    do té doby zůstávají u současné sezony.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-2">
                                <Label htmlFor="season-name">
                                    Název sezony
                                </Label>
                                <Input
                                    id="season-name"
                                    name="name"
                                    required
                                    autoFocus
                                    autoComplete="off"
                                    placeholder="2027/28"
                                    aria-invalid={errors.name !== undefined}
                                />
                                <InputError message={errors.name} />
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
                                    data-test="store-season"
                                >
                                    {processing && <Spinner />}
                                    Vytvořit sezonu
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function SeasonTable({ seasons }: { seasons: SeasonListItem[] }) {
    return (
        <div className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Sezona</TableHead>
                        <TableHead className="text-right">Týmy</TableHead>
                        <TableHead>
                            <span className="sr-only">Akce</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {seasons.map((season) => (
                        <TableRow key={season.id}>
                            <TableCell>
                                <span className="inline-flex flex-wrap items-center gap-2">
                                    <span className="font-medium tabular-nums">
                                        {season.name}
                                    </span>
                                    {season.isCurrent && (
                                        <Badge data-test="current-season">
                                            Aktuální
                                        </Badge>
                                    )}
                                </span>
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {season.teamSeasonsCount}
                            </TableCell>
                            <TableCell className="text-right">
                                {!season.isCurrent && (
                                    <MarkAsCurrentDialog season={season} />
                                )}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function MarkAsCurrentDialog({ season }: { season: SeasonListItem }) {
    const [isOpen, setIsOpen] = useState(false);
    const [isMarking, setIsMarking] = useState(false);

    function markSeasonAsCurrent(): void {
        router.post(
            markAsCurrent.url(season.id),
            {},
            {
                preserveScroll: true,
                onStart: () => setIsMarking(true),
                onFinish: () => setIsMarking(false),
                onSuccess: () => setIsOpen(false),
            },
        );
    }

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    data-test="mark-season-as-current"
                >
                    Nastavit jako aktuální
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Nastavit sezonu {season.name} jako aktuální?
                    </DialogTitle>
                    <DialogDescription>
                        Kalendáře a veřejné stránky týmů začnou ukazovat rozpisy
                        sezony {season.name} a automatický import bude stahovat
                        jen její týmy. Sezona, se kterou právě pracujete v
                        administraci, se nezmění.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Zrušit</Button>
                    </DialogClose>
                    <Button
                        disabled={isMarking}
                        data-test="confirm-mark-season-as-current"
                        onClick={markSeasonAsCurrent}
                    >
                        {isMarking && <Spinner />}
                        Nastavit jako aktuální
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
