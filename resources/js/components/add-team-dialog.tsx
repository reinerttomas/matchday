import { Form, useHttp } from '@inertiajs/react';
import { ExternalLink, Plus } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ChangeEvent, FocusEvent, ReactNode } from 'react';
import { toast } from 'sonner';
import { CategoryBadge } from '@/components/category-badge';
import { CategorySelect } from '@/components/category-select';
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
import type { CarryOverTeam, FederationTeam, TeamCategory } from '@/types';

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
    categories,
    calendarUrlTemplate,
}: {
    seasonName: string;
    carryOverTeams: CarryOverTeam[];
    categories: TeamCategory[];
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
                    categories={categories}
                    calendarUrlTemplate={calendarUrlTemplate}
                    onSuccess={() => setIsOpen(false)}
                />
            </DialogContent>
        </Dialog>
    );
}

function AddTeamForm({
    carryOverTeams,
    categories,
    calendarUrlTemplate,
    onSuccess,
}: {
    carryOverTeams: CarryOverTeam[];
    categories: TeamCategory[];
    calendarUrlTemplate: string;
    onSuccess: () => void;
}) {
    const [address, setAddress] = useState('');
    const [name, setName] = useState('');
    const isNameEdited = useRef(false);
    const [category, setCategory] = useState('');
    const isCategoryPickedByHand = useRef(false);
    const [choice, setChoice] = useState<Choice>('new');
    const [teamId, setTeamId] = useState('');
    const isChoiceMadeByHand = useRef(false);
    const {
        lookup,
        lookupError,
        lookUp,
        lookUpAgain,
        isLookingUpAgain,
        isLookedUp,
        reset,
    } = useFederationTeamLookup((team, isLookedUpAgain) => {
        if (!isLookedUpAgain) {
            if (!isNameEdited.current && team.name !== null) {
                setName(team.name);
            }

            if (!isCategoryPickedByHand.current && team.category !== null) {
                setCategory(team.category.value);
            }
        }

        preselect(carryOverTeamOf(carryOverTeams, team));
    });
    const isResolved =
        lookup.status === 'found' || lookup.status === 'unavailable';
    const carryOverTeam =
        lookup.status === 'found'
            ? carryOverTeamOf(carryOverTeams, lookup.team)
            : undefined;
    // The hint names the competition only while the category shown is the one guessed from it.
    const guessedFromCompetition =
        lookup.status === 'found' &&
        lookup.team.category !== null &&
        lookup.team.category.value === category
            ? lookup.team.competition
            : null;

    function preselect(carryOverTeam: CarryOverTeam | undefined): void {
        if (isChoiceMadeByHand.current) {
            return;
        }

        setChoice(carryOverTeam === undefined ? 'new' : 'carry-over');
        setTeamId(carryOverTeam === undefined ? '' : String(carryOverTeam.id));
    }

    function pickCategory(value: string): void {
        setCategory(value);
        isCategoryPickedByHand.current = true;
        // The server matches the team from the previous season by name and category, so another category may continue another team.
        void lookUpAgain(value);
    }

    function lookUpAddress(value: string): void {
        void lookUp(value, isCategoryPickedByHand.current ? category : '');
    }

    function changeAddress(event: ChangeEvent<HTMLInputElement>): void {
        setAddress(event.target.value);

        if ((event.nativeEvent as InputEvent).inputType === 'insertFromPaste') {
            lookUpAddress(event.target.value);
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

        lookUpAddress(event.target.value);
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
                                <NameAndCategoryFields
                                    name={name}
                                    category={category}
                                    categories={categories}
                                    guessedFromCompetition={
                                        guessedFromCompetition
                                    }
                                    isNameDisabled={choice === 'carry-over'}
                                    nameError={errors.name}
                                    categoryError={errors.category}
                                    onNameChange={(value) => {
                                        setName(value);
                                        isNameEdited.current = true;
                                    }}
                                    onCategoryChange={pickCategory}
                                />
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
                                            carryOverTeam === undefined ? (
                                                'Tým z minulé sezony'
                                            ) : (
                                                <>
                                                    Pokračuje{' '}
                                                    {carryOverTeam.name}
                                                    <CategoryBadge
                                                        category={
                                                            carryOverTeam.category
                                                        }
                                                        data-test="carry-over-team-category"
                                                    />
                                                </>
                                            )
                                        }
                                        description={
                                            carryOverTeam === undefined
                                                ? 'Kalendář hráčů zůstane stejný.'
                                                : 'Stejný název na svazu i kategorie.'
                                        }
                                    />
                                </RadioGroup>
                                {choice === 'new' ? (
                                    <CalendarUrlPreview
                                        name={name}
                                        category={categories.find(
                                            (item) => item.value === category,
                                        )}
                                        calendarUrlTemplate={
                                            calendarUrlTemplate
                                        }
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
                                disabled={
                                    processing ||
                                    !isResolved ||
                                    isLookingUpAgain
                                }
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
 * "unavailable", so the administrator fills the form in by hand; only the response to the latest request counts.
 *
 * A category passed along replaces the guessed one when the server matches the carry-over team. lookUpAgain asks again for the
 * looked-up address with a category picked by hand, keeping the summary shown meanwhile.
 */
function useFederationTeamLookup(
    onFound: (team: FederationTeam, isLookedUpAgain: boolean) => void,
) {
    const { setData, get, cancel, clearErrors, errors } = useHttp<
        { federation_team_page_url: string; category: string },
        FederationTeam
    >({ federation_team_page_url: '', category: '' });
    const [lookup, setLookup] = useState<Lookup>({ status: 'idle' });
    const [isLookingUpAgain, setIsLookingUpAgain] = useState(false);
    const latestAddress = useRef<string | null>(null);
    const latestRequest = useRef(0);
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
        setIsLookingUpAgain(false);
        setLookup({ status: 'idle' });
    }

    async function lookUp(address: string, category: string): Promise<void> {
        if (address.trim() === '' || isLookedUp(address)) {
            return;
        }

        latestAddress.current = address;
        setLookup({ status: 'loading' });
        await send(address, category, false);
    }

    async function lookUpAgain(category: string): Promise<void> {
        if (latestAddress.current === null || lookup.status !== 'found') {
            return;
        }

        await send(latestAddress.current, category, true);
    }

    async function send(
        address: string,
        category: string,
        isLookedUpAgain: boolean,
    ): Promise<void> {
        const request = ++latestRequest.current;
        const isLatest = () =>
            request === latestRequest.current && isLookedUp(address);

        function markUnavailable(message: string): void {
            if (isLatest()) {
                setIsLookingUpAgain(false);
                toast.warning(message);
                setLookup({ status: 'unavailable' });
            }
        }

        cancel();
        clearErrors();
        setIsLookingUpAgain(isLookedUpAgain);
        // The cancelled request clears the abort controller when it settles, so starting before that would leave this request impossible to cancel.
        await pendingRequest.current;

        if (!isLatest()) {
            return;
        }

        setData({ federation_team_page_url: address, category });
        pendingRequest.current = get(lookUpTeam.url(), {
            onSuccess: (team) => {
                if (isLatest()) {
                    setIsLookingUpAgain(false);
                    setLookup({ status: 'found', team });
                    onFound(team, isLookedUpAgain);
                }
            },
            onError: () => {
                if (isLatest()) {
                    setIsLookingUpAgain(false);
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
        lookUpAgain,
        isLookingUpAgain,
        isLookedUp,
        reset,
    };
}

const UNAVAILABLE_MESSAGE =
    'Český florbal teď neodpovídá. Název ani soutěž se nedoplní, vyplňte je ručně.';

// The server matches the federation name of the team's latest team season together with the category, so the dialog only finds the team it names.
function carryOverTeamOf(
    carryOverTeams: CarryOverTeam[],
    team: FederationTeam,
): CarryOverTeam | undefined {
    return carryOverTeams.find(
        (carryOverTeam) => carryOverTeam.id === team.carryOverTeamId,
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

/**
 * The new team's name with its category beside it. The category stays enabled for a team carried over too, because the team
 * from the previous season is matched by it; the name is disabled then, as a team carried over keeps its own.
 */
function NameAndCategoryFields({
    name,
    category,
    categories,
    guessedFromCompetition,
    isNameDisabled,
    nameError,
    categoryError,
    onNameChange,
    onCategoryChange,
}: {
    name: string;
    category: string;
    categories: TeamCategory[];
    guessedFromCompetition: string | null;
    isNameDisabled: boolean;
    nameError: string | undefined;
    categoryError: string | undefined;
    onNameChange: (name: string) => void;
    onCategoryChange: (category: string) => void;
}) {
    return (
        <div className="grid gap-2">
            <div className="grid gap-4 sm:grid-cols-[1fr_12rem]">
                <Field
                    className="gap-2"
                    data-invalid={nameError !== undefined}
                    data-disabled={isNameDisabled}
                >
                    <FieldLabel htmlFor="new-team-name">Název týmu</FieldLabel>
                    <Input
                        id="new-team-name"
                        name="name"
                        required
                        disabled={isNameDisabled}
                        autoComplete="off"
                        placeholder="FBC Kutná Hora B"
                        value={name}
                        onChange={(event) => onNameChange(event.target.value)}
                        aria-invalid={nameError !== undefined}
                        aria-describedby="new-team-calendar-url"
                    />
                    <FieldError>{nameError}</FieldError>
                </Field>
                <Field
                    className="gap-2"
                    data-invalid={categoryError !== undefined}
                >
                    <FieldLabel htmlFor="new-team-category">
                        Kategorie
                    </FieldLabel>
                    <CategorySelect
                        id="new-team-category"
                        categories={categories}
                        value={category}
                        onValueChange={onCategoryChange}
                        isInvalid={categoryError !== undefined}
                        placeholder="Vyberte kategorii"
                        data-test="new-team-category-select"
                    />
                    <FieldError>{categoryError}</FieldError>
                </Field>
            </div>
            {guessedFromCompetition !== null && (
                <FieldDescription className="text-xs">
                    Kategorie odhadnutá ze soutěže „{guessedFromCompetition}“.
                </FieldDescription>
            )}
        </div>
    );
}

function ChoiceCard({
    value,
    title,
    description,
    disabled = false,
}: {
    value: Choice;
    title: ReactNode;
    description: string;
    disabled?: boolean;
}) {
    const id = `add-team-choice-${value}`;

    return (
        <FieldLabel htmlFor={id}>
            <Field orientation="horizontal" data-disabled={disabled}>
                <FieldContent>
                    <FieldTitle className="flex-wrap">{title}</FieldTitle>
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

function CalendarUrlPreview({
    name,
    category,
    calendarUrlTemplate,
}: {
    name: string;
    category: TeamCategory | undefined;
    calendarUrlTemplate: string;
}) {
    // Like on the server, a new team's slug is made of its name and its category (ADR 0003).
    const slug =
        slugify(name) === ''
            ? 'nazev-tymu'
            : slugify(`${name} ${category?.label ?? ''}`);

    return (
        <FieldDescription id="new-team-calendar-url" className="grid gap-1">
            <span>
                Adresa kalendáře:{' '}
                <span
                    className="font-mono text-xs break-all text-foreground"
                    data-test="calendar-url-preview"
                >
                    {calendarUrlTemplate.replace(':slug', slug)}
                </span>
            </span>
            <span>Adresu kalendáře později nepůjde změnit.</span>
        </FieldDescription>
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
                            <CategoryBadge category={team.category} />
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
