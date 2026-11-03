import { Form, useHttp } from '@inertiajs/react';
import { ExternalLink, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ChangeEvent, FocusEvent } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
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
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldLabel,
    FieldTitle,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { slugify } from '@/lib/utils';
import { lookup as lookUpTeam, store } from '@/routes/teams';
import type { CarryOverTeam, FederationTeam } from '@/types';

type Lookup =
    | { status: 'idle' }
    | { status: 'loading' }
    | { status: 'found'; team: FederationTeam }
    | { status: 'unavailable' }
    | { status: 'invalid' };

type Choice = 'new' | 'carry-over';

export function AddTeamDialog({
    seasonName,
    carryOverTeams,
    calendarUrlTemplate,
}: {
    seasonName: string;
    carryOverTeams: CarryOverTeam[];
    calendarUrlTemplate: string;
}) {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button className="self-start" data-test="add-team">
                    <Plus />
                    Přidat tým
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Přidat tým do sezony {seasonName}</DialogTitle>
                    <DialogDescription>
                        Vložte adresu stránky týmu na ceskyflorbal.cz, název a
                        soutěž se doplní. Hned po přidání se stáhne rozpis
                        zápasů týmu.
                    </DialogDescription>
                </DialogHeader>
                <AddTeamForm
                    carryOverTeams={carryOverTeams}
                    calendarUrlTemplate={calendarUrlTemplate}
                    onSuccess={() => setIsOpen(false)}
                />
            </DialogContent>
        </Dialog>
    );
}

function AddTeamForm({
    carryOverTeams,
    calendarUrlTemplate,
    onSuccess,
}: {
    carryOverTeams: CarryOverTeam[];
    calendarUrlTemplate: string;
    onSuccess: () => void;
}) {
    const [address, setAddress] = useState('');
    const [name, setName] = useState('');
    const isNameEdited = useRef(false);
    const [choice, setChoice] = useState<Choice>('new');
    const [teamId, setTeamId] = useState('');
    const isChoiceMadeByHand = useRef(false);
    const { lookup, lookupError, lookUp, isLookedUp, reset } =
        useFederationTeamLookup((team) => {
            if (!isNameEdited.current && team.name !== null) {
                setName(team.name);
            }

            preselect(sameNamedCarryOverTeam(carryOverTeams, team.name));
        });
    const isResolved =
        lookup.status === 'found' || lookup.status === 'unavailable';
    const sameNamedTeam =
        lookup.status === 'found'
            ? sameNamedCarryOverTeam(carryOverTeams, lookup.team.name)
            : undefined;

    function preselect(sameNamedTeam: CarryOverTeam | undefined): void {
        if (isChoiceMadeByHand.current) {
            return;
        }

        setChoice(sameNamedTeam === undefined ? 'new' : 'carry-over');
        setTeamId(sameNamedTeam === undefined ? '' : String(sameNamedTeam.id));
    }

    function changeAddress(event: ChangeEvent<HTMLInputElement>): void {
        setAddress(event.target.value);

        if ((event.nativeEvent as InputEvent).inputType === 'insertFromPaste') {
            void lookUp(event.target.value);
        } else if (!isLookedUp(event.target.value)) {
            reset();
            preselect(undefined);
        }
    }

    function leaveAddress(event: FocusEvent<HTMLInputElement>): void {
        // Leaving the field to close the dialog is no request to look the team up.
        if (
            event.relatedTarget instanceof Element &&
            event.relatedTarget.closest('[data-slot="dialog-close"]') !== null
        ) {
            return;
        }

        void lookUp(event.target.value);
    }

    return (
        <Form
            {...store.form()}
            options={{ preserveScroll: true }}
            onSuccess={onSuccess}
            className="grid gap-4"
        >
            {({ processing, errors }) => {
                const addressError =
                    lookupError ?? errors.federation_team_page_url;

                return (
                    <>
                        <Field
                            className="gap-2"
                            data-invalid={addressError !== undefined}
                        >
                            <FieldLabel htmlFor="federation-team-page-url">
                                Stránka týmu na ceskyflorbal.cz
                            </FieldLabel>
                            <Input
                                id="federation-team-page-url"
                                name="federation_team_page_url"
                                required
                                autoFocus
                                autoComplete="off"
                                inputMode="url"
                                placeholder="https://www.ceskyflorbal.cz/team/detail/overview/45019"
                                value={address}
                                onChange={changeAddress}
                                onBlur={leaveAddress}
                                aria-invalid={addressError !== undefined}
                                aria-describedby="federation-team-page-url-description"
                            />
                            <FieldDescription id="federation-team-page-url-description">
                                Otevřete stránku týmu na ceskyflorbal.cz, na
                                kterékoli záložce (Přehled, Zápasy, Soupiska…),
                                a zkopírujte její adresu.
                            </FieldDescription>
                            <FieldError>{addressError}</FieldError>
                        </Field>
                        {lookup.status === 'loading' && (
                            <FederationTeamSummarySkeleton />
                        )}
                        {lookup.status === 'found' && (
                            <FederationTeamSummary team={lookup.team} />
                        )}
                        {isResolved && (
                            <>
                                <RadioGroup
                                    value={choice}
                                    onValueChange={(value) => {
                                        setChoice(value as Choice);
                                        isChoiceMadeByHand.current = true;
                                    }}
                                    className="grid gap-2 sm:grid-cols-2"
                                >
                                    <ChoiceCard
                                        value="new"
                                        title="Nový tým"
                                        description="Tým v aplikaci ještě není."
                                    />
                                    <ChoiceCard
                                        value="carry-over"
                                        disabled={carryOverTeams.length === 0}
                                        title={
                                            sameNamedTeam === undefined
                                                ? 'Tým z minulé sezony'
                                                : `Pokračuje ${sameNamedTeam.name}`
                                        }
                                        description={
                                            sameNamedTeam === undefined
                                                ? 'Kalendář hráčů zůstane stejný.'
                                                : 'Stejný název jako tým z minulé sezony'
                                        }
                                    />
                                </RadioGroup>
                                {choice === 'new' ? (
                                    <NewTeamFields
                                        name={name}
                                        error={errors.name}
                                        calendarUrlTemplate={
                                            calendarUrlTemplate
                                        }
                                        onChange={(value) => {
                                            setName(value);
                                            isNameEdited.current = true;
                                        }}
                                    />
                                ) : (
                                    <CarryOverTeamFields
                                        carryOverTeams={carryOverTeams}
                                        teamId={teamId}
                                        error={errors.team_id}
                                        onChange={(value) => {
                                            setTeamId(value);
                                            isChoiceMadeByHand.current = true;
                                        }}
                                    />
                                )}
                            </>
                        )}
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Zrušit
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                disabled={processing || !isResolved}
                                data-test="store-team"
                            >
                                {processing && <Spinner />}
                                Přidat tým
                            </Button>
                        </DialogFooter>
                    </>
                );
            }}
        </Form>
    );
}

/**
 * Look the team up on its federation team page without a page visit. A lookup that ceskyflorbal.cz cannot answer comes back
 * "unavailable", so the administrator fills the form in by hand; only the response for the latest address counts.
 */
function useFederationTeamLookup(onFound: (team: FederationTeam) => void) {
    const { setData, get, cancel, clearErrors, errors } = useHttp<
        { federation_team_page_url: string },
        FederationTeam
    >({ federation_team_page_url: '' });
    const [lookup, setLookup] = useState<Lookup>({ status: 'idle' });
    const latestAddress = useRef<string | null>(null);
    const pendingRequest = useRef<Promise<unknown>>(Promise.resolve());

    useEffect(
        () => () => {
            latestAddress.current = null;
            cancel();
        },
        [cancel],
    );

    function isLookedUp(address: string): boolean {
        return address === latestAddress.current;
    }

    function reset(): void {
        latestAddress.current = null;
        cancel();
        clearErrors();
        setLookup({ status: 'idle' });
    }

    async function lookUp(address: string): Promise<void> {
        if (address.trim() === '' || isLookedUp(address)) {
            return;
        }

        latestAddress.current = address;
        const isLatest = () => isLookedUp(address);

        function markUnavailable(message: string): void {
            if (isLatest()) {
                toast.warning(message);
                setLookup({ status: 'unavailable' });
            }
        }

        cancel();
        clearErrors();
        setLookup({ status: 'loading' });
        // The cancelled request clears the abort controller when it settles, so starting before that would leave this request impossible to cancel.
        await pendingRequest.current;

        if (!isLatest()) {
            return;
        }

        setData('federation_team_page_url', address);
        pendingRequest.current = get(lookUpTeam.url(), {
            onSuccess: (team) => {
                if (isLatest()) {
                    setLookup({ status: 'found', team });
                    onFound(team);
                }
            },
            onError: () => {
                if (isLatest()) {
                    setLookup({ status: 'invalid' });
                }
            },
            onHttpException: (response) =>
                markUnavailable(
                    response.status === 429
                        ? 'Příliš mnoho dotazů za sebou, zkuste to za chvíli.'
                        : UNAVAILABLE_MESSAGE,
                ),
            onNetworkError: () => markUnavailable(UNAVAILABLE_MESSAGE),
        }).catch(() => undefined);
    }

    return {
        lookup,
        lookupError:
            lookup.status === 'invalid'
                ? errors.federation_team_page_url
                : undefined,
        lookUp,
        isLookedUp,
        reset,
    };
}

const UNAVAILABLE_MESSAGE =
    'Český florbal teď neodpovídá. Název ani soutěž se nedoplní, vyplňte je ručně.';

function normalizedTeamName(name: string): string {
    return name.trim().replace(/\s+/g, ' ').toLocaleLowerCase('cs');
}

// Exact on purpose: a club often has several teams ("FBC Kutná Hora", "FBC Kutná Hora B"), so a prefix match would pick the wrong one.
function sameNamedCarryOverTeam(
    carryOverTeams: CarryOverTeam[],
    name: string | null,
): CarryOverTeam | undefined {
    if (name === null) {
        return undefined;
    }

    return carryOverTeams.find(
        (team) => normalizedTeamName(team.name) === normalizedTeamName(name),
    );
}

function FederationTeamSummarySkeleton() {
    return (
        <Card
            className="gap-2 rounded-lg p-4 shadow-none"
            data-test="federation-team-summary-skeleton"
        >
            <Skeleton className="h-4 w-1/2" />
            <Skeleton className="h-4 w-2/3" />
            <Skeleton className="h-4 w-1/3" />
            <Skeleton className="h-4 w-1/4" />
        </Card>
    );
}

function FederationTeamSummary({ team }: { team: FederationTeam }) {
    return (
        <Card
            className="rounded-lg p-4 shadow-none"
            data-test="federation-team-summary"
        >
            <dl className="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                <dt className="text-muted-foreground">Tým</dt>
                <dd className="font-medium">{team.name ?? '—'}</dd>
                <dt className="text-muted-foreground">Soutěž</dt>
                <dd>{team.competition ?? '—'}</dd>
                <dt className="text-muted-foreground">Sezona</dt>
                <dd>{team.season}</dd>
                <dt className="text-muted-foreground">ID svazu</dt>
                <dd>
                    <a
                        href={team.overviewUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 underline underline-offset-4"
                        data-test="federation-team-overview"
                    >
                        {team.externalId}
                        <ExternalLink className="size-3" />
                    </a>
                </dd>
            </dl>
        </Card>
    );
}

function ChoiceCard({
    value,
    title,
    description,
    disabled = false,
}: {
    value: Choice;
    title: string;
    description: string;
    disabled?: boolean;
}) {
    const id = `add-team-choice-${value}`;

    return (
        <FieldLabel htmlFor={id}>
            <Field orientation="horizontal" data-disabled={disabled}>
                <FieldContent>
                    <FieldTitle>{title}</FieldTitle>
                    <FieldDescription>{description}</FieldDescription>
                </FieldContent>
                <RadioGroupItem
                    value={value}
                    id={id}
                    disabled={disabled}
                    data-test={`${value}-team-choice`}
                />
            </Field>
        </FieldLabel>
    );
}

function NewTeamFields({
    name,
    error,
    calendarUrlTemplate,
    onChange,
}: {
    name: string;
    error: string | undefined;
    calendarUrlTemplate: string;
    onChange: (name: string) => void;
}) {
    const slug = slugify(name);

    return (
        <Field className="gap-2" data-invalid={error !== undefined}>
            <FieldLabel htmlFor="new-team-name">Název týmu</FieldLabel>
            <Input
                id="new-team-name"
                name="name"
                required
                autoComplete="off"
                placeholder="FBC Kutná Hora B"
                value={name}
                onChange={(event) => onChange(event.target.value)}
                aria-invalid={error !== undefined}
                aria-describedby="new-team-calendar-url"
            />
            <FieldError>{error}</FieldError>
            <FieldDescription id="new-team-calendar-url" className="grid gap-1">
                <span>
                    Adresa kalendáře:{' '}
                    <span
                        className="font-mono text-xs break-all text-foreground"
                        data-test="calendar-url-preview"
                    >
                        {calendarUrlTemplate.replace(
                            ':slug',
                            slug === '' ? 'nazev-tymu' : slug,
                        )}
                    </span>
                </span>
                <span>Adresu kalendáře později nepůjde změnit.</span>
            </FieldDescription>
        </Field>
    );
}

function CarryOverTeamFields({
    carryOverTeams,
    teamId,
    error,
    onChange,
}: {
    carryOverTeams: CarryOverTeam[];
    teamId: string;
    error: string | undefined;
    onChange: (teamId: string) => void;
}) {
    return (
        <Field className="gap-2" data-invalid={error !== undefined}>
            <FieldLabel htmlFor="carry-over-team-id">Tým</FieldLabel>
            <Select
                name="team_id"
                required
                value={teamId}
                onValueChange={onChange}
            >
                <SelectTrigger
                    id="carry-over-team-id"
                    className="w-full"
                    aria-invalid={error !== undefined}
                    data-test="carry-over-team-select"
                >
                    <SelectValue placeholder="Vyberte tým" />
                </SelectTrigger>
                <SelectContent>
                    {carryOverTeams.map((team) => (
                        <SelectItem key={team.id} value={String(team.id)}>
                            {team.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <FieldError>{error}</FieldError>
            <FieldDescription>
                Název i adresa kalendáře zůstanou stejné, takže hráči nemusí
                kalendář přidávat znovu.
            </FieldDescription>
        </Field>
    );
}
