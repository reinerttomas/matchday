import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import type { ImportStatus } from '@/types';

/**
 * How an import ended, coloured so a failed or aborted import stands out from an ok one.
 */
export function ImportStatusBadge({
    status,
    label,
}: {
    status: ImportStatus;
    label: string;
}) {
    switch (status) {
        case 'ok':
            return (
                <Badge
                    variant="outline"
                    className="border-emerald-600/30 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    {label}
                </Badge>
            );
        case 'error':
            return <Badge variant="destructive">{label}</Badge>;
        case 'aborted':
            return (
                <Badge
                    variant="outline"
                    className="border-amber-600/30 bg-amber-50 text-amber-800 dark:border-amber-400/30 dark:bg-amber-950 dark:text-amber-300"
                >
                    {label}
                </Badge>
            );
        case 'running':
            return (
                <Badge variant="secondary">
                    <Spinner className="size-3" />
                    {label}
                </Badge>
            );
    }
}
