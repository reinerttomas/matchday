import { usePoll } from '@inertiajs/react';
import { useEffect } from 'react';

const POLL_INTERVAL_MS = 2000;

/**
 * Reloads the page every few seconds while an import of the selected team season runs, so the new data shows up without a manual reload; the reload that reports the import finished stops it.
 */
export function useImportPolling(isImportRunning: boolean): void {
    const { start, stop } = usePoll(POLL_INTERVAL_MS, {}, { autoStart: false });

    useEffect(() => {
        if (isImportRunning) {
            start();
        } else {
            stop();
        }
    }, [isImportRunning, start, stop]);
}
