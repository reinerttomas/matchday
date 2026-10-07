import { cn } from '@/lib/utils';
import type { MatchupPart } from '@/types';

/**
 * Names a fixture's sides in "Home – Away" order with our team in bold.
 */
export function Matchup({
    parts,
    className,
}: {
    parts: MatchupPart[];
    className?: string;
}) {
    return (
        <p className={cn('break-words', className)}>
            {parts.map((part, position) =>
                part.isOurTeam ? (
                    <strong key={position} className="font-semibold">
                        {part.text}
                    </strong>
                ) : (
                    <span key={position}>{part.text}</span>
                ),
            )}
        </p>
    );
}
