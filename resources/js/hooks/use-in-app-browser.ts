import { useSyncExternalStore } from 'react';
import { detectInAppBrowser } from '@/lib/in-app-browser';

function isInAppBrowser(): boolean {
    return detectInAppBrowser() !== null;
}

function subscribeToNothing(): () => void {
    return () => {};
}

/**
 * Whether the page runs in an app's own browser, such as Messenger's or WhatsApp's. The server cannot see that, so it
 * renders for a regular browser and the client switches after hydration.
 */
export function useIsInAppBrowser(): boolean {
    return useSyncExternalStore(
        subscribeToNothing,
        isInAppBrowser,
        () => false,
    );
}
