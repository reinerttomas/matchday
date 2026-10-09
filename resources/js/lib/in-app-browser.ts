import InAppSpy from 'inapp-spy';

/**
 * Names the app whose own browser the page runs in, such as "messenger" or "whatsapp", or returns null in a regular
 * browser. An in-app browser inapp-spy cannot name still differs from a regular browser, so it is named "webview".
 */
export function detectInAppBrowser(): string | null {
    const { isInApp, appKey } = InAppSpy();

    if (!isInApp) {
        return null;
    }

    return appKey ?? 'webview';
}
