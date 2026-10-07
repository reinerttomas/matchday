import { router, useHttp } from '@inertiajs/react';
import { Check, CheckCheck, Copy, ExternalLink, Send } from 'lucide-react';
import { useRef, useState } from 'react';
import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useClipboard } from '@/hooks/use-clipboard';
import { store as markAsSent } from '@/routes/changes/sent';
import { show as showSummary } from '@/routes/changes/summary';

type SummaryState =
    | { status: 'loading' }
    | { status: 'failed' }
    | { status: 'loaded'; summary: string | null };

/**
 * Opens an import's change summary, served by the server, for the administrator to tweak, send to the team's WhatsApp group and mark as sent; edits live only in the dialog.
 */
export function ChangeSummaryDialog({ importId }: { importId: number }) {
    const [isOpen, setIsOpen] = useState(false);
    const [summaryState, setSummaryState] = useState<SummaryState>({
        status: 'loading',
    });
    const [text, setText] = useState('');
    const [isWhatsAppOpened, setIsWhatsAppOpened] = useState(false);
    const [isMarking, setIsMarking] = useState(false);
    const { submit } = useHttp<
        Record<string, never>,
        { summary: string | null }
    >();

    async function openDialog(): Promise<void> {
        setIsOpen(true);
        setSummaryState({ status: 'loading' });
        setIsWhatsAppOpened(false);

        try {
            const { summary } = await submit(showSummary(importId));

            setText(summary ?? '');
            setSummaryState({ status: 'loaded', summary });
        } catch {
            setSummaryState({ status: 'failed' });
        }
    }

    function markSummaryAsSent(): void {
        router.post(
            markAsSent.url(importId),
            {},
            {
                preserveScroll: true,
                onStart: () => setIsMarking(true),
                onFinish: () => setIsMarking(false),
                onSuccess: () => setIsOpen(false),
            },
        );
    }

    const hasSummary =
        summaryState.status === 'loaded' && summaryState.summary !== null;
    const canMarkAsSent =
        summaryState.status === 'loaded' &&
        (summaryState.summary === null || isWhatsAppOpened);

    return (
        <Dialog
            open={isOpen}
            onOpenChange={(isOpening) =>
                isOpening ? void openDialog() : setIsOpen(false)
            }
        >
            <DialogTrigger asChild>
                <Button size="sm" data-test="send-change-summary">
                    <Send />
                    <span className="sm:hidden">Poslat</span>
                    <span className="hidden sm:inline">
                        Poslat do WhatsAppu
                    </span>
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Souhrn změn pro WhatsApp</DialogTitle>
                    <DialogDescription>
                        {summaryState.status === 'loaded' &&
                        summaryState.summary === null
                            ? 'Import změnil jen údaje, které týmu nemá smysl oznamovat (například že zápas už není dohrávka), takže není co poslat. Stačí souhrn označit jako odeslaný.'
                            : 'Upravte text podle potřeby, otevřete WhatsApp, vyberte skupinu týmu a zprávu odešlete. Pak souhrn označte jako odeslaný.'}
                    </DialogDescription>
                </DialogHeader>
                {summaryState.status === 'loading' && (
                    <Skeleton
                        className="h-48 w-full"
                        aria-label="Načítám souhrn změn"
                    />
                )}
                {summaryState.status === 'failed' && (
                    <AlertError
                        title="Souhrn změn se nepodařilo načíst."
                        errors={['Zavřete okno a zkuste to znovu.']}
                    />
                )}
                {hasSummary && (
                    <SummaryText
                        id={`change-summary-${importId}`}
                        text={text}
                        onChange={setText}
                    />
                )}
                <DialogFooter>
                    {hasSummary && (
                        <OpenWhatsAppButton
                            text={text}
                            onOpen={() => setIsWhatsAppOpened(true)}
                        />
                    )}
                    {canMarkAsSent && (
                        <Button
                            variant={hasSummary ? 'outline' : 'default'}
                            disabled={isMarking}
                            data-test="mark-as-sent"
                            onClick={markSummaryAsSent}
                        >
                            {isMarking ? <Spinner /> : <CheckCheck />}
                            Označit jako odesláno
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function SummaryText({
    id,
    text,
    onChange,
}: {
    id: string;
    text: string;
    onChange: (text: string) => void;
}) {
    const [copiedText, copy] = useClipboard();
    const isCopied = copiedText === text;
    const textareaRef = useRef<HTMLTextAreaElement>(null);

    // Some browsers block the clipboard, so the text is selected for the administrator to copy by hand.
    async function copyText(): Promise<void> {
        if (!(await copy(text))) {
            textareaRef.current?.select();
        }
    }

    return (
        <div className="flex min-w-0 flex-col gap-2">
            <div className="flex items-center justify-between gap-2">
                <Label htmlFor={id}>Text zprávy</Label>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    data-test="copy-change-summary"
                    onClick={() => void copyText()}
                >
                    {isCopied ? <Check aria-hidden /> : <Copy aria-hidden />}
                    {isCopied ? 'Zkopírováno' : 'Kopírovat text'}
                </Button>
            </div>
            <Textarea
                ref={textareaRef}
                id={id}
                data-test="change-summary-text"
                className="max-h-[50vh] min-h-40"
                value={text}
                onChange={(event) => onChange(event.target.value)}
            />
        </div>
    );
}

function OpenWhatsAppButton({
    text,
    onOpen,
}: {
    text: string;
    onOpen: () => void;
}) {
    if (text.trim() === '') {
        return (
            <Button disabled>
                <ExternalLink />
                Otevřít WhatsApp
            </Button>
        );
    }

    return (
        <Button asChild>
            <a
                href={whatsAppUrl(text)}
                target="_blank"
                rel="noopener noreferrer"
                data-test="open-whatsapp"
                onClick={onOpen}
                onAuxClick={onOpen}
            >
                <ExternalLink />
                Otevřít WhatsApp
            </a>
        </Button>
    );
}

/**
 * A link equivalent to ChangeSummaryWriter::whatsAppUrl() on the server, built here because the administrator may have edited the text.
 */
function whatsAppUrl(text: string): string {
    return `https://wa.me/?text=${encodeURIComponent(text)}`;
}
