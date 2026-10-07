import { ThemeProvider, useTheme } from 'next-themes';
import type { ReactNode } from 'react';
import { useSyncExternalStore } from 'react';

export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const subscribeToNothing = () => () => {};

/**
 * Keeps the appearance in next-themes, so the toasts follow it too; it reads the same localStorage key the app always used.
 */
export function AppearanceProvider({ children }: { children: ReactNode }) {
    return (
        <ThemeProvider attribute="class" storageKey="appearance">
            {children}
        </ThemeProvider>
    );
}

export function useAppearance(): UseAppearanceReturn {
    const { theme, resolvedTheme, setTheme } = useTheme();

    // The server can't read localStorage, so hydration renders the system appearance like the server did.
    const isHydrated = useSyncExternalStore(
        subscribeToNothing,
        () => true,
        () => false,
    );

    const appearance: Appearance = isHydrated
        ? ((theme as Appearance | undefined) ?? 'system')
        : 'system';

    const resolvedAppearance: ResolvedAppearance =
        isHydrated && resolvedTheme === 'dark' ? 'dark' : 'light';

    const updateAppearance = (mode: Appearance): void => {
        setTheme(mode);

        // The server renders the `dark` class from this cookie before any script runs.
        setCookie('appearance', mode);
    };

    return { appearance, resolvedAppearance, updateAppearance } as const;
}
