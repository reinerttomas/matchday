import {
    Apple,
    CalendarDays,
    Check,
    ChevronDown,
    Copy,
    Mail,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useClipboard } from '@/hooks/use-clipboard';
import { useDevice } from '@/hooks/use-device';
import type { Device } from '@/hooks/use-device';
import { recordTeamPageEvent } from '@/lib/team-page-events';
import type { CalendarLinks, TeamPageAction } from '@/types';

type InstructionsTab = 'google' | 'android' | 'iphone' | 'outlook';

type SubscribeOption = {
    label: string;
    icon: LucideIcon;
    hrefFor: (calendar: CalendarLinks) => string;
    opensNewTab: boolean;
    action: TeamPageAction;
};

const INSTRUCTIONS: { tab: InstructionsTab; label: string; steps: string[] }[] =
    [
        {
            tab: 'google',
            label: 'Google',
            steps: [
                'Klikněte na tlačítko „Přidat do Google Kalendáře“ nebo „Google Kalendář“ pod „Jiný kalendář“ a přihlaste se ke svému účtu Google.',
                'Přidání kalendáře potvrďte tlačítkem „Přidat“.',
                'Kalendář najdete vlevo v seznamu „Další kalendáře“.',
                'Když se potvrzení nezobrazí, klikněte v Google Kalendáři vedle „Další kalendáře“ na „+“ → „Z adresy URL“, vložte zkopírovanou adresu a klikněte na „Přidat kalendář“.',
            ],
        },
        {
            tab: 'android',
            label: 'Android',
            steps: [
                'Aplikace Kalendář Google neumí přidat kalendář podle adresy, proto ho přidáte přes web: klepněte na „Přidat do Google Kalendáře“ a přihlaste se stejným účtem Google, jaký máte v telefonu.',
                'Když se otevře jen mobilní verze bez potvrzení, zapněte v nabídce prohlížeče „Verze pro počítač“ a klepněte na tlačítko znovu. Případně postup dokončete na počítači.',
                'Přidání kalendáře potvrďte tlačítkem „Přidat“.',
                'V aplikaci Kalendář Google otevřete nabídku → „Nastavení“, vyberte nový kalendář a zapněte „Synchronizace“.',
            ],
        },
        {
            tab: 'iphone',
            label: 'iPhone',
            steps: [
                'Klepněte na tlačítko „Přidat do kalendáře v iPhonu“.',
                'Odběr kalendáře potvrďte tlačítkem „Odebírat“ a pak přidání potvrďte.',
                'Zápasy se objeví v aplikaci Kalendář.',
                'Když se nic nestane, otevřete aplikaci Kalendář → „Kalendáře“ → „Přidat kalendář“ → „Přidat odebíraný kalendář“, vložte zkopírovanou adresu, klepněte na „Odebírat“ a přidání potvrďte.',
            ],
        },
        {
            tab: 'outlook',
            label: 'Outlook',
            steps: [
                'Pod „Jiný kalendář“ klikněte na tlačítko „Outlook“ a přihlaste se ke svému účtu Microsoft.',
                'Zkontrolujte název kalendáře a potvrďte přidání.',
                'Když se okno nezobrazí, otevřete v Outlooku kalendář → „Přidat kalendář“ → „Přihlásit se k odběru z webu“, vložte zkopírovanou adresu a potvrďte.',
            ],
        },
    ];

const INSTRUCTIONS_TAB_FOR_DEVICE: Record<Device, InstructionsTab> = {
    apple: 'iphone',
    android: 'android',
    other: 'google',
};

const APPLE_OPTION: SubscribeOption = {
    label: 'iPhone / Mac',
    icon: Apple,
    hrefFor: (calendar) => calendar.webcal,
    opensNewTab: false,
    action: 'webcal',
};

const GOOGLE_OPTION: SubscribeOption = {
    label: 'Google Kalendář',
    icon: CalendarDays,
    hrefFor: (calendar) => calendar.google,
    opensNewTab: true,
    action: 'google',
};

const OUTLOOK_OPTION: SubscribeOption = {
    label: 'Outlook',
    icon: Mail,
    hrefFor: (calendar) => calendar.outlook,
    opensNewTab: true,
    action: 'outlook',
};

const PRIMARY_OPTION_FOR_DEVICE: Record<Device, SubscribeOption> = {
    apple: { ...APPLE_OPTION, label: 'Přidat do kalendáře v iPhonu' },
    android: { ...GOOGLE_OPTION, label: 'Přidat do Google Kalendáře' },
    other: { ...GOOGLE_OPTION, label: 'Přidat do Google Kalendáře' },
};

function otherOptions(primaryOption: SubscribeOption): SubscribeOption[] {
    return [GOOGLE_OPTION, APPLE_OPTION, OUTLOOK_OPTION].filter(
        (option) => option.action !== primaryOption.action,
    );
}

/**
 * Lets a player subscribe to the team's calendar: one button for their device up front, the other calendars and the
 * address to copy folded away and, in a dialog, instructions per device for adding it by hand. It never links to the
 * feed itself, because downloading it would import a copy that never updates.
 */
export default function CalendarSubscription({
    slug,
    calendar,
}: {
    slug: string;
    calendar: CalendarLinks;
}) {
    const device = useDevice();
    const primaryOption = PRIMARY_OPTION_FOR_DEVICE[device];
    const [isHelpOpen, setIsHelpOpen] = useState(false);

    // Only the player opening the dialog counts as help_open; the dialog opening itself after a blocked copy does not.
    function changeHelpOpen(isOpen: boolean): void {
        if (isOpen) {
            recordTeamPageEvent(slug, 'help_open');
        }

        setIsHelpOpen(isOpen);
    }

    return (
        <Card className="gap-4 py-5">
            <CardHeader className="px-5">
                <CardTitle>
                    <h2 id="calendar-subscription">Zápasy do kalendáře</h2>
                </CardTitle>
                <CardDescription>
                    Kalendář se aktualizuje sám, přeložené zápasy se v něm
                    přesunou.
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-col gap-2 px-5">
                <SubscribeButton
                    slug={slug}
                    calendar={calendar}
                    option={primaryOption}
                    className="h-auto min-h-9 justify-start bg-blue-600 py-2 whitespace-normal text-white hover:bg-blue-700"
                />
                {device === 'android' && (
                    // Google's phone app cannot subscribe, and without sync the calendar never reaches the phone.
                    <p className="text-sm text-muted-foreground">
                        Pak v aplikaci Kalendář Google otevřete Nastavení,
                        vyberte kalendář týmu a zapněte Synchronizace.
                    </p>
                )}
                <Collapsible
                    onOpenChange={(isOpen) => {
                        if (isOpen) {
                            recordTeamPageEvent(slug, 'other_options_open');
                        }
                    }}
                >
                    <CollapsibleTrigger asChild>
                        <Button
                            variant="link"
                            size="sm"
                            className="group h-auto min-h-6 px-0 py-1 text-muted-foreground hover:text-foreground has-[>svg]:px-0"
                        >
                            Jiný kalendář
                            <ChevronDown
                                aria-hidden
                                className="transition-transform group-data-[state=open]:rotate-180"
                            />
                        </Button>
                    </CollapsibleTrigger>
                    <CollapsibleContent className="flex flex-col items-start gap-2 pt-1">
                        {otherOptions(primaryOption).map((option) => (
                            <SubscribeButton
                                key={option.action}
                                slug={slug}
                                calendar={calendar}
                                option={option}
                                variant="outline"
                                className="w-full justify-start"
                            />
                        ))}
                        <CopyAddressButton
                            address={calendar.address}
                            onCopied={() =>
                                recordTeamPageEvent(slug, 'copy_address')
                            }
                            // Without the clipboard the player copies the address by hand from the dialog.
                            onCopyFailed={() => setIsHelpOpen(true)}
                        />
                    </CollapsibleContent>
                </Collapsible>
            </CardContent>
            <CardFooter className="flex-col items-start gap-1 border-t px-5 [.border-t]:pt-4">
                <Dialog open={isHelpOpen} onOpenChange={changeHelpOpen}>
                    <DialogTrigger asChild>
                        <Button
                            variant="link"
                            size="sm"
                            className="h-auto min-h-6 px-0 py-1 text-muted-foreground hover:text-foreground has-[>svg]:px-0"
                        >
                            Nefunguje to?
                        </Button>
                    </DialogTrigger>
                    <DialogContent
                        className="max-h-[90vh] overflow-y-auto"
                        data-test="help-dialog"
                    >
                        <DialogHeader>
                            <DialogTitle>Nefunguje to?</DialogTitle>
                            <DialogDescription>
                                Kalendář jde přidat i ručně podle adresy. Změny
                                se projeví do několika hodin (v Google Kalendáři
                                až do jednoho dne) a oznamujeme je také ve
                                skupině týmu na WhatsAppu.
                            </DialogDescription>
                        </DialogHeader>
                        <Instructions device={device} />
                        <CalendarAddress
                            address={calendar.address}
                            onCopied={() =>
                                recordTeamPageEvent(slug, 'copy_address')
                            }
                        />
                    </DialogContent>
                </Dialog>
            </CardFooter>
        </Card>
    );
}

function SubscribeButton({
    slug,
    calendar,
    option,
    variant = 'default',
    className,
}: {
    slug: string;
    calendar: CalendarLinks;
    option: SubscribeOption;
    variant?: 'default' | 'outline';
    className: string;
}) {
    return (
        <Button asChild variant={variant} className={className}>
            <a
                href={option.hrefFor(calendar)}
                data-test="subscribe-button"
                // Only records the tap; the link still navigates on its own.
                onClick={() => recordTeamPageEvent(slug, option.action)}
                {...(option.opensNewTab && {
                    target: '_blank',
                    rel: 'noreferrer',
                })}
            >
                <option.icon aria-hidden />
                {option.label}
            </a>
        </Button>
    );
}

function CopyAddressButton({
    address,
    onCopied,
    onCopyFailed,
}: {
    address: string;
    onCopied: () => void;
    onCopyFailed: () => void;
}) {
    const [copiedText, copy] = useClipboard();
    const isCopied = copiedText === address;

    async function copyAddress(): Promise<void> {
        if (await copy(address)) {
            onCopied();
        } else {
            onCopyFailed();
        }
    }

    return (
        <>
            <Button
                type="button"
                variant="link"
                size="sm"
                className="h-auto min-h-6 px-0 py-1 text-muted-foreground hover:text-foreground has-[>svg]:px-0"
                data-test="copy-address"
                onClick={() => void copyAddress()}
            >
                {isCopied ? <Check aria-hidden /> : <Copy aria-hidden />}
                {isCopied ? 'Adresa zkopírována' : 'Kopírovat adresu'}
            </Button>
            <span
                role="status"
                aria-live="polite"
                className="sr-only"
                data-test="copy-status"
            >
                {isCopied ? 'Adresa zkopírována' : ''}
            </span>
        </>
    );
}

function CalendarAddress({
    address,
    onCopied,
}: {
    address: string;
    onCopied: () => void;
}) {
    const [copiedText, copy] = useClipboard();
    const isCopied = copiedText === address;
    const inputRef = useRef<HTMLInputElement>(null);

    // Some in-app browsers block the clipboard, so the address is selected for the player to copy by hand.
    async function copyAddress(): Promise<void> {
        if (await copy(address)) {
            onCopied();
        } else {
            inputRef.current?.select();
        }
    }

    return (
        <div className="flex flex-col gap-2">
            <Label htmlFor="calendar-address">Adresa kalendáře</Label>
            <div className="flex gap-2">
                <Input
                    ref={inputRef}
                    id="calendar-address"
                    data-test="calendar-address"
                    readOnly
                    value={address}
                    onFocus={(event) => event.currentTarget.select()}
                />
                <Button
                    type="button"
                    variant="outline"
                    className="shrink-0"
                    data-test="dialog-copy-address"
                    onClick={() => void copyAddress()}
                >
                    {isCopied ? <Check aria-hidden /> : <Copy aria-hidden />}
                    {isCopied ? 'Zkopírováno' : 'Kopírovat'}
                </Button>
            </div>
        </div>
    );
}

function Instructions({ device }: { device: Device }) {
    const [pickedTab, setPickedTab] = useState<InstructionsTab | null>(null);

    return (
        <div className="flex flex-col gap-3">
            <h3 className="text-sm font-medium">Postup krok za krokem</h3>
            <Tabs
                value={pickedTab ?? INSTRUCTIONS_TAB_FOR_DEVICE[device]}
                onValueChange={(tab) => setPickedTab(tab as InstructionsTab)}
                data-test="instructions"
            >
                <TabsList
                    aria-label="Postup podle zařízení"
                    // The four labels don't fit side by side on the narrowest phones.
                    className="grid w-full grid-cols-2 gap-[3px] group-data-[orientation=horizontal]/tabs:h-auto sm:flex sm:gap-0 sm:group-data-[orientation=horizontal]/tabs:h-9"
                >
                    {INSTRUCTIONS.map(({ tab, label }) => (
                        <TabsTrigger
                            key={tab}
                            value={tab}
                            data-test={`instructions-tab-${tab}`}
                        >
                            {label}
                        </TabsTrigger>
                    ))}
                </TabsList>
                {INSTRUCTIONS.map(({ tab, steps }) => (
                    <TabsContent key={tab} value={tab}>
                        <ol className="list-decimal space-y-1.5 pl-5 text-sm">
                            {steps.map((step) => (
                                <li key={step}>{step}</li>
                            ))}
                        </ol>
                    </TabsContent>
                ))}
            </Tabs>
        </div>
    );
}
