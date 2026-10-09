import { detectInAppBrowser } from '@/lib/in-app-browser';
import { store } from '@/routes/team-page-events';
import type { TeamPageAction } from '@/types';

/**
 * Records what the player did on the public team page and in which app's own browser, if any. A beacon outlives the
 * page, so a tap that navigates away still gets recorded, and nothing waits for it. Without beacon support, or when
 * the browser refuses to queue it, the event is dropped: the statistics never get in the player's way.
 */
export function recordTeamPageEvent(
    slug: string,
    action: TeamPageAction,
): void {
    if (typeof navigator.sendBeacon !== 'function') {
        return;
    }

    const event = new URLSearchParams({ action });
    const inAppBrowser = detectInAppBrowser();

    if (inAppBrowser !== null) {
        event.set('in_app_browser', inAppBrowser);
    }

    navigator.sendBeacon(store.url(slug), event);
}
