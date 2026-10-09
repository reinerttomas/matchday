import { useSyncExternalStore } from 'react';

export type Device = 'apple' | 'android' | 'other';

/**
 * iPadOS reports itself as a Mac, so "Macintosh" covers iPads as well as Macs.
 */
const APPLE_DEVICE = /iPhone|iPad|iPod|Macintosh/;

const ANDROID_DEVICE = /Android/;

function detectDevice(): Device {
    if (ANDROID_DEVICE.test(navigator.userAgent)) {
        return 'android';
    }

    return APPLE_DEVICE.test(navigator.userAgent) ? 'apple' : 'other';
}

function subscribeToNothing(): () => void {
    return () => {};
}

/**
 * The server cannot see the device, so it renders for "other" and the client switches to the real device after hydration.
 */
export function useDevice(): Device {
    return useSyncExternalStore(
        subscribeToNothing,
        detectDevice,
        () => 'other',
    );
}
