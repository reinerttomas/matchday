import { Check, Copy, ExternalLink } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useClipboard } from '@/hooks/use-clipboard';
import { useDevice } from '@/hooks/use-device';
import { recordTeamPageEvent } from '@/lib/team-page-events';

const OPEN_IN_BROWSER_STEPS = [
    'Klepněte na ⋯ v rohu obrazovky.',
    'Zvolte „Otevřít v prohlížeči“.',
    'Přidejte si kalendář jedním tlačítkem.',
];

/**
 * Opens the page in the player's default browser; without a package it is not tied to Chrome.
 */
function androidEscapeUrl(): string {
    const { protocol, host, pathname, search } = window.location;

    return `intent://${host}${pathname}${search}#Intent;scheme=${protocol.replace(':', '')};end`;
}

/**
 * Takes the place of the subscription card in an app's own browser, where the subscribe buttons do nothing, and shows
 * the player the way to their real browser. It renders only on the client, and the Android escape is a tap, because
 * in-app browsers block navigation nobody tapped. Messenger on iPhone ignores Safari's own scheme, so only Android gets
 * a button; everywhere else the player follows the steps.
 */
export default function InAppBrowserNotice({ slug }: { slug: string }) {
    const device = useDevice();

    return (
        <Alert data-test="in-app-browser-notice">
            <ExternalLink aria-hidden />
            <AlertTitle className="line-clamp-none">
                <h2 id="open-in-browser">Otevřete stránku v prohlížeči</h2>
            </AlertTitle>
            <AlertDescription>
                <p>
                    Kalendář jde přidat jen z prohlížeče (Safari nebo Chrome).
                </p>
                <div className="mt-2 flex w-full flex-col gap-3">
                    {device === 'android' && (
                        <Button asChild>
                            <a
                                href={androidEscapeUrl()}
                                data-test="escape-in-app-browser"
                                onClick={() =>
                                    recordTeamPageEvent(slug, 'escape_intent')
                                }
                            >
                                Otevřít v prohlížeči
                            </a>
                        </Button>
                    )}
                    <ol
                        className="list-decimal space-y-1 pl-5"
                        data-test="open-in-browser-steps"
                    >
                        {OPEN_IN_BROWSER_STEPS.map((step) => (
                            <li key={step}>{step}</li>
                        ))}
                    </ol>
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
