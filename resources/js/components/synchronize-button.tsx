import { useForm } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/imports';

/**
 * Starts a manual import of the selected team season; disabled while one runs, so it can't be started twice.
 */
export function SynchronizeButton({
    isImportRunning,
}: {
    isImportRunning: boolean;
}) {
    const { post, processing } = useForm({});
    const isBusy = isImportRunning || processing;

    return (
        <Button
            disabled={isBusy}
            onClick={() => post(store.url(), { preserveScroll: true })}
        >
            {isBusy ? <Spinner aria-label="Stahuji rozpis" /> : <RefreshCw />}
            Synchronizovat
        </Button>
    );
}
