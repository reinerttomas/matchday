import { Badge } from '@/components/ui/badge';
import type { RevisionPart } from '@/types';

/**
 * A revision read as one phrase, such as "Čas: ~~TBD~~ → 9:00", with the old value struck through and the new one emphasised.
 */
export function Revision({ parts }: { parts: RevisionPart[] }) {
    return parts.map((part, position) => (
        <RevisionPartText key={position} part={part} />
    ));
}

/**
 * A fixture's revisions one per line, the field names in a muted column of their own and the old → new values beside them; a new fixture takes a whole line.
 */
export function RevisionList({ revisions }: { revisions: RevisionPart[][] }) {
    return (
        <ul className="grid grid-cols-[max-content_minmax(0,1fr)] gap-x-4 divide-y text-sm">
            {revisions.map((parts, position) => (
                <li
                    key={position}
                    className="col-span-2 grid grid-cols-subgrid py-2 first:pt-0 last:pb-0"
                >
                    <RevisionLine parts={parts} />
                </li>
            ))}
        </ul>
    );
}

function RevisionLine({ parts }: { parts: RevisionPart[] }) {
    // Only a phrase that starts with the field splits into columns; anything else reads as a whole, so no words are lost.
    if (parts[0]?.kind !== 'field') {
        return (
            <span className="col-span-2">
                <Revision parts={parts} />
            </span>
        );
    }

    // The column stands in for the separator that follows the field name in the phrase.
    const valueParts = parts.slice(1);
    const values =
        valueParts[0]?.kind === 'text' ? valueParts.slice(1) : valueParts;

    return (
        <>
            <span className="text-muted-foreground">{parts[0].text}</span>
            <span className="break-words">
                <Revision parts={values} />
            </span>
        </>
    );
}

function RevisionPartText({ part }: { part: RevisionPart }) {
    switch (part.kind) {
        case 'added':
            return (
                <Badge
                    variant="outline"
                    className="border-sky-600/30 bg-sky-50 text-sky-700 dark:border-sky-400/30 dark:bg-sky-950 dark:text-sky-300"
                >
                    {part.text}
                </Badge>
            );
        case 'old_value':
            return <s className="text-muted-foreground">{part.text}</s>;
        case 'new_value':
            return <span className="font-medium">{part.text}</span>;
        default:
            return <span className="text-muted-foreground">{part.text}</span>;
    }
}
