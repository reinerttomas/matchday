import { Form, Head } from '@inertiajs/react';
import { MapPin, Pencil } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { index, update } from '@/routes/venues';
import type { VenueListItem } from '@/types';

type Props = {
    venues: VenueListItem[];
    missingAddressSummary: string;
    calendarLocationTemplate: string;
};

export default function Venues({
    venues,
    missingAddressSummary,
    calendarLocationTemplate,
}: Props) {
    return (
        <>
            <Head title="Haly" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <Heading
                    title="Haly"
                    description="Haly a jejich názvy přebírá import z ceskyflorbal.cz. Adresa se hráčům zobrazuje v místě konání zápasů v kalendáři."
                />
                {venues.length === 0 ? (
                    <NoVenues />
                ) : (
                    <div className="flex flex-col gap-4">
                        <p
                            className="text-sm text-muted-foreground"
                            data-test="missing-address-summary"
                        >
                            {missingAddressSummary}
                        </p>
                        <VenueTable
                            venues={venues}
                            calendarLocationTemplate={calendarLocationTemplate}
                        />
                    </div>
                )}
            </div>
        </>
    );
}

Venues.layout = {
    breadcrumbs: [
        {
            title: 'Haly',
            href: index(),
        },
    ],
};

function NoVenues() {
    return (
        <Empty className="border">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <MapPin />
                </EmptyMedia>
                <EmptyTitle>Zatím tu není žádná hala</EmptyTitle>
                <EmptyDescription>
                    Haly se doplní při importu rozpisu zápasů.
                </EmptyDescription>
            </EmptyHeader>
        </Empty>
    );
}

function VenueTable({
    venues,
    calendarLocationTemplate,
}: {
    venues: VenueListItem[];
    calendarLocationTemplate: string;
}) {
    return (
        <div className="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Hala</TableHead>
                        <TableHead>Adresa</TableHead>
                        <TableHead className="text-right">Zápasy</TableHead>
                        <TableHead>
                            <span className="sr-only">Akce</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {venues.map((venue) => (
                        <VenueRow
                            key={venue.id}
                            venue={venue}
                            calendarLocationTemplate={calendarLocationTemplate}
                        />
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function VenueRow({
    venue,
    calendarLocationTemplate,
}: {
    venue: VenueListItem;
    calendarLocationTemplate: string;
}) {
    const [isEditing, setIsEditing] = useState(false);

    return (
        <TableRow>
            <TableCell className="font-medium whitespace-normal">
                {venue.name}
            </TableCell>
            <TableCell className="whitespace-normal">
                {venue.address ?? (
                    <Button
                        variant="link"
                        className="h-auto p-0"
                        data-test="fill-in-address"
                        onClick={() => setIsEditing(true)}
                    >
                        Doplnit adresu
                    </Button>
                )}
            </TableCell>
            <TableCell className="text-right tabular-nums">
                {venue.fixturesCount}
            </TableCell>
            <TableCell className="text-right">
                <Button
                    variant="ghost"
                    size="icon"
                    data-test="edit-venue"
                    onClick={() => setIsEditing(true)}
                >
                    <Pencil />
                    <span className="sr-only">
                        Upravit adresu haly {venue.name}
                    </span>
                </Button>
                <Dialog open={isEditing} onOpenChange={setIsEditing}>
                    <DialogContent>
                        <EditAddressForm
                            venue={venue}
                            calendarLocationTemplate={calendarLocationTemplate}
                            onSuccess={() => setIsEditing(false)}
                        />
                    </DialogContent>
                </Dialog>
            </TableCell>
        </TableRow>
    );
}

function EditAddressForm({
    venue,
    calendarLocationTemplate,
    onSuccess,
}: {
    venue: VenueListItem;
    calendarLocationTemplate: string;
    onSuccess: () => void;
}) {
    const [address, setAddress] = useState(venue.address ?? '');

    return (
        <Form
            {...update.form(venue.id)}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
            className="grid gap-4 text-left"
        >
            {({ processing, errors }) => (
                <>
                    <DialogHeader>
                        <DialogTitle>Adresa haly {venue.name}</DialogTitle>
                        <DialogDescription>
                            Název haly přebírá import z ceskyflorbal.cz, upravit
                            jde jen adresa. Další importy ji nepřepíšou.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor={`venue-${venue.id}-address`}>
                            Adresa
                        </Label>
                        <Input
                            id={`venue-${venue.id}-address`}
                            name="address"
                            required
                            autoFocus
                            autoComplete="off"
                            placeholder="Čáslavská 274, Kutná Hora"
                            value={address}
                            onChange={(event) => setAddress(event.target.value)}
                            aria-invalid={errors.address !== undefined}
                            aria-describedby={`venue-${venue.id}-location`}
                        />
                        <InputError message={errors.address} />
                        <p
                            id={`venue-${venue.id}-location`}
                            className="text-sm text-muted-foreground"
                        >
                            Místo v kalendáři:{' '}
                            <span
                                className="text-foreground"
                                data-test="calendar-location-preview"
                            >
                                {calendarLocation(
                                    calendarLocationTemplate,
                                    venue.name,
                                    address,
                                )}
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
                            data-test="update-venue"
                        >
                            {processing && <Spinner />}
                            Uložit adresu
                        </Button>
                    </DialogFooter>
                </>
            )}
        </Form>
    );
}

/**
 * Fill in the location the calendar feed writes: the venue's name alone until it has an address.
 */
function calendarLocation(
    template: string,
    venueName: string,
    address: string,
): string {
    const trimmedAddress = address.trim();

    if (trimmedAddress === '') {
        return venueName;
    }

    // One pass, so a name that happens to contain ":address" is not replaced again.
    return template.replace(/:(venue|address)/g, (_placeholder, key: string) =>
        key === 'venue' ? venueName : trimmedAddress,
    );
}
