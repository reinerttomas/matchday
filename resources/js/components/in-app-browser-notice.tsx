import { Check, Copy, ExternalLink } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClipboard } from '@/hooks/use-clipboard';
import { useDevice } from '@/hooks/use-device';
import type { Device } from '@/hooks/use-device';
import { recordTeamPageEvent } from '@/lib/team-page-events';
import type { TeamPageAction } from '@/types';

type EscapeOption = {
    href: string;
    action: TeamPageAction;
    label: string;
    hint: string | null;
};

/**
 * Opens the page in the player's default browser; without a package it is not tied to Chrome.
 */
function androidEscapeUrl(): string {
    const { protocol, host, pathname, search } = window.location;

    return `intent://${host}${pathname}${search}#Intent;scheme=${protocol.replace(':', '')};end`;
}

/**
 * Safari's own scheme, which iOS 16 and some of Meta's in-app browsers ignore, hence the manual path next to it.
 */
function safariEscapeUrl(): string {
    return `x-safari-${window.location.href}`;
}

/**
 * Other devices have no known scheme that leaves an in-app browser, so the player gets only the manual path there.
 */
function escapeOption(device: Device): EscapeOption | null {
    switch (device) {
        case 'android':
            return {
                href: androidEscapeUrl(),
                action: 'escape_intent',
                label: 'Otevřít v prohlížeči',
                hint: null,
            };
        case 'apple':
            return {
                href: safariEscapeUrl(),
                action: 'escape_safari',
                label: 'Otevřít v Safari',
                hint: 'Pokud se nic nestane, klepněte na ⋯ a zvolte „Otevřít v prohlížeči“.',
            };
        case 'other':
            return null;
    }
}

/**
 * Asks an app's own browser to send the player to their real browser, where Google sign-in and webcal:// links work. It
 * renders only on the client, and every escape is a tap, because in-app browsers block navigation nobody tapped.
 */
export default function InAppBrowserNotice({ slug }: { slug: string }) {
    const escape = escapeOption(useDevice());

    return (
        <Alert data-test="in-app-browser-notice">
            <ExternalLink aria-hidden />
            <AlertTitle className="line-clamp-none">
                Otevřete stránku v prohlížeči
            </AlertTitle>
            <AlertDescription>
                <p>
                    V prohlížeči uvnitř aplikace se kalendář nemusí přidat. Ze
                    Safari nebo Chrome to funguje.
                </p>
                <div className="mt-2 flex w-full flex-col gap-2">
                    {escape ? (
                        <>
                            <Button asChild>
                                <a
                                    href={escape.href}
                                    data-test="escape-in-app-browser"
                                    onClick={() =>
                                        recordTeamPageEvent(slug, escape.action)
                                    }
                                >
                                    {escape.label}
                                </a>
                            </Button>
                            {escape.hint && (
                                <p className="text-xs">{escape.hint}</p>
                            )}
                        </>
                    ) : (
                        <p className="text-xs">
                            Otevřete stránku v prohlížeči přes nabídku aplikace.
                        </p>
                    )}
                    <CopyPageLink
                        onCopied={() =>
                            recordTeamPageEvent(slug, 'copy_page_link')
                        }
                    />
                </div>
            </AlertDescription>
        </Alert>
    );
}

function CopyPageLink({ onCopied }: { onCopied: () => void }) {
    const [copiedText, copy] = useClipboard();
    const [isClipboardBlocked, setIsClipboardBlocked] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);
    const pageLink = window.location.href;
    const isCopied = copiedText === pageLink;

    // Some in-app browsers block the clipboard, so the link is selected for the player to copy by hand.
    useEffect(() => {
        if (isClipboardBlocked) {
            inputRef.current?.focus();
        }
    }, [isClipboardBlocked]);

    async function copyPageLink(): Promise<void> {
        if (await copy(pageLink)) {
            onCopied();
        } else {
            setIsClipboardBlocked(true);
        }
    }

    return (
        <>
            <Button
                type="button"
                variant="outline"
                data-test="copy-page-link"
                onClick={() => void copyPageLink()}
            >
                {isCopied ? <Check aria-hidden /> : <Copy aria-hidden />}
                {isCopied ? 'Odkaz zkopírován' : 'Kopírovat odkaz'}
            </Button>
            <span
                role="status"
                aria-live="polite"
                className="sr-only"
                data-test="copy-page-link-status"
            >
                {isCopied ? 'Odkaz zkopírován' : ''}
            </span>
            {isClipboardBlocked && (
                <div className="flex flex-col gap-2">
                    <Label htmlFor="page-link">Odkaz na stránku</Label>
                    <Input
                        ref={inputRef}
                        id="page-link"
                        data-test="page-link"
                        readOnly
                        value={pageLink}
                        onFocus={(event) => event.currentTarget.select()}
                    />
                </div>
            )}
        </>
    );
}
