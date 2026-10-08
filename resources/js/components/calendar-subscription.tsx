import { Apple, CalendarDays, Check, Copy, Mail } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useRef, useState, useSyncExternalStore } from 'react';
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
import type { CalendarLinks } from '@/types';

type Device = 'apple' | 'android' | 'other';

type InstructionsTab = 'google' | 'android' | 'iphone' | 'outlook';

type SubscribeOption = {
    label: string;
    icon: LucideIcon;
    href: string;
    opensNewTab: boolean;
};

/**
 * iPadOS reports itself as a Mac, so "Macintosh" covers iPads as well as Macs.
 */
const APPLE_DEVICE = /iPhone|iPad|iPod|Macintosh/;

const ANDROID_DEVICE = /Android/;

const INSTRUCTIONS: { tab: InstructionsTab; label: string; steps: string[] }[] =
    [
        {
            tab: 'google',
            label: 'Google',
            steps: [
                'Klikněte na tlačítko „Google Kalendář“ a přihlaste se ke svému účtu Google.',
                'Přidání kalendáře potvrďte tlačítkem „Přidat“.',
                'Kalendář najdete vlevo v seznamu „Další kalendáře“.',
                'Když se potvrzení nezobrazí, klikněte v Google Kalendáři vedle „Další kalendáře“ na „+“ → „Z adresy URL“, vložte zkopírovanou adresu a klikněte na „Přidat kalendář“.',
            ],
        },
        {
            tab: 'android',
            label: 'Android',
            steps: [
                'Aplikace Kalendář Google neumí přidat kalendář podle adresy, proto ho přidáte přes web: klepněte na „Google Kalendář“ a přihlaste se stejným účtem Google, jaký máte v telefonu.',
                'Když se otevře jen mobilní verze bez potvrzení, zapněte v nabídce prohlížeče „Verze pro počítač“ a klepněte na tlačítko znovu. Případně postup dokončete na počítači.',
                'Přidání kalendáře potvrďte tlačítkem „Přidat“.',
                'V aplikaci Kalendář Google otevřete nabídku → „Nastavení“, vyberte nový kalendář a zapněte „Synchronizace“.',
            ],
        },
        {
            tab: 'iphone',
            label: 'iPhone',
            steps: [
                'Klepněte na tlačítko „iPhone / Mac“.',
                'Odběr kalendáře potvrďte tlačítkem „Odebírat“ a pak přidání potvrďte.',
                'Zápasy se objeví v aplikaci Kalendář.',
                'Když se nic nestane, otevřete aplikaci Kalendář → „Kalendáře“ → „Přidat kalendář“ → „Přidat odebíraný kalendář“, vložte zkopírovanou adresu, klepněte na „Odebírat“ a přidání potvrďte.',
            ],
        },
        {
            tab: 'outlook',
            label: 'Outlook',
            steps: [
                'Klikněte na tlačítko „Outlook“ a přihlaste se ke svému účtu Microsoft.',
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

function detectDevice(): Device {
    if (ANDROID_DEVICE.test(navigator.userAgent)) {
        return 'android';
    }

    return APPLE_DEVICE.test(navigator.userAgent) ? 'apple' : 'other';
}

function subscribeToNothing(): () => void {
    return () => {};
}

/**
 * The server cannot see the device, so it renders for "other" and the client switches to the real device after hydration.
 */
function useDevice(): Device {
    return useSyncExternalStore(
        subscribeToNothing,
        detectDevice,
        () => 'other',
    );
}

function subscribeOptions(
    calendar: CalendarLinks,
    device: Device,
): SubscribeOption[] {
    const apple: SubscribeOption = {
        label: 'iPhone / Mac',
        icon: Apple,
        href: calendar.webcal,
        opensNewTab: false,
    };
    const google: SubscribeOption = {
        label: 'Google Kalendář',
        icon: CalendarDays,
        href: calendar.google,
        opensNewTab: true,
    };
    const outlook: SubscribeOption = {
        label: 'Outlook',
        icon: Mail,
        href: calendar.outlook,
        opensNewTab: true,
    };

    return device === 'apple'
        ? [apple, google, outlook]
        : [google, apple, outlook];
}

/**
 * Lets a player subscribe to the team's calendar: one-tap buttons ordered for their device, the address to copy and,
 * in a dialog, instructions per device for adding it by hand. It never links to the feed itself, because downloading
 * it would import a copy that never updates.
 */
export default function CalendarSubscription({
    calendar,
}: {
    calendar: CalendarLinks;
}) {
    const device = useDevice();
    const [isHelpOpen, setIsHelpOpen] = useState(false);

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
                {subscribeOptions(calendar, device).map((option, index) => (
                    <Button
                        key={option.label}
                        asChild
                        variant={index === 0 ? 'default' : 'outline'}
                        className={
                            index === 0
                                ? 'justify-start bg-blue-600 text-white hover:bg-blue-700'
                                : 'justify-start'
                        }
                    >
                        <a
                            href={option.href}
                            data-test="subscribe-button"
                            {...(option.opensNewTab && {
                                target: '_blank',
                                rel: 'noreferrer',
                            })}
                        >
                            <option.icon aria-hidden />
                            {option.label}
                        </a>
                    </Button>
                ))}
            </CardContent>
            <CardFooter className="flex-col items-start gap-1 border-t px-5 [.border-t]:pt-4">
                <CopyAddressButton
                    address={calendar.address}
                    // Without the clipboard the player copies the address by hand from the dialog.
                    onCopyFailed={() => setIsHelpOpen(true)}
                />
                <Dialog open={isHelpOpen} onOpenChange={setIsHelpOpen}>
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
                        <CalendarAddress address={calendar.address} />
                    </DialogContent>
                </Dialog>
            </CardFooter>
        </Card>
    );
}

function CopyAddressButton({
    address,
    onCopyFailed,
}: {
    address: string;
    onCopyFailed: () => void;
}) {
    const [copiedText, copy] = useClipboard();
    const isCopied = copiedText === address;

    async function copyAddress(): Promise<void> {
        if (!(await copy(address))) {
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

function CalendarAddress({ address }: { address: string }) {
    const [copiedText, copy] = useClipboard();
    const isCopied = copiedText === address;
    const inputRef = useRef<HTMLInputElement>(null);

    // Some in-app browsers block the clipboard, so the address is selected for the player to copy by hand.
    async function copyAddress(): Promise<void> {
        if (!(await copy(address))) {
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
