import { Check, Copy } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useRef, useState, useSyncExternalStore } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useClipboard } from '@/hooks/use-clipboard';
import type { CalendarLinks } from '@/types';

type Device = 'apple' | 'android' | 'other';

type InstructionsTab = 'google' | 'android' | 'iphone' | 'outlook';

type SubscribeOption = {
    label: string;
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

function useIsHydrated(): boolean {
    return useSyncExternalStore(
        subscribeToNothing,
        () => true,
        () => false,
    );
}

/**
 * Lets a player subscribe to the team's calendar: one-tap buttons ordered for their device, the address to copy,
 * instructions per device and, on a wide screen, a QR code to continue on a phone. It never links to the feed itself,
 * because downloading it would import a copy that never updates.
 */
export default function CalendarSubscription({
    calendar,
    pageUrl,
}: {
    calendar: CalendarLinks;
    pageUrl: string;
}) {
    const device = useDevice();
    // The QR code only serves wide screens, so it is left out of the server-rendered HTML that phones load first.
    const isHydrated = useIsHydrated();

    const google: SubscribeOption = {
        label: 'Google Kalendář',
        href: calendar.google,
        opensNewTab: true,
    };
    const apple: SubscribeOption = {
        label: 'iPhone / Mac',
        href: calendar.webcal,
        opensNewTab: false,
    };
    const outlook: SubscribeOption = {
        label: 'Outlook',
        href: calendar.outlook,
        opensNewTab: true,
    };
    const options =
        device === 'apple'
            ? [apple, google, outlook]
            : [google, apple, outlook];

    return (
        <section aria-labelledby="calendar-subscription">
            <Card>
                <div className="flex gap-6 px-6">
                    <div className="flex min-w-0 flex-1 flex-col gap-4">
                        <CardHeader className="px-0">
                            <CardTitle>
                                <h2 id="calendar-subscription">
                                    Přidat zápasy do kalendáře
                                </h2>
                            </CardTitle>
                            <CardDescription>
                                Kalendář se aktualizuje sám. Změny se projeví do
                                několika hodin (v Google Kalendáři až do jednoho
                                dne) a oznamujeme je také ve skupině týmu na
                                WhatsAppu.
                            </CardDescription>
                        </CardHeader>
                        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                            {options.map((option, index) => (
                                <Button
                                    key={option.label}
                                    asChild
                                    variant={
                                        index === 0 ? 'default' : 'outline'
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
                                        {option.label}
                                    </a>
                                </Button>
                            ))}
                        </div>
                    </div>
                    {isHydrated && (
                        <div
                            data-test="qr-code"
                            className="hidden w-36 shrink-0 flex-col items-center gap-2 text-center lg:flex"
                        >
                            {/* A white frame keeps the code scannable in dark mode. */}
                            <div className="rounded-md bg-white p-2">
                                <QRCodeSVG
                                    value={pageUrl}
                                    size={112}
                                    title="QR kód této stránky"
                                />
                            </div>
                            <p className="text-xs text-muted-foreground">
                                U počítače? Naskenujte kód a pokračujte v
                                telefonu.
                            </p>
                        </div>
                    )}
                </div>
                <CardContent className="flex flex-col gap-6">
                    <CalendarAddress address={calendar.address} />
                    <Instructions device={device} />
                </CardContent>
            </Card>
        </section>
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
                    data-test="copy-address"
                    onClick={() => void copyAddress()}
                >
                    {isCopied ? <Check aria-hidden /> : <Copy aria-hidden />}
                    {isCopied ? 'Zkopírováno' : 'Kopírovat'}
                </Button>
            </div>
            <span
                role="status"
                aria-live="polite"
                className="sr-only"
                data-test="copy-status"
            >
                {isCopied ? 'Zkopírováno' : ''}
            </span>
        </div>
    );
}

function Instructions({ device }: { device: Device }) {
    // Until the player picks a tab, the device decides it, which changes once after hydration.
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
