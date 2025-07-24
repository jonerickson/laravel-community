import { useCallback, useEffect } from 'react';
import { useCookie } from './use-cookie';

export type Appearance = 'light' | 'dark' | 'system';

const prefersDark = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const applyTheme = (appearance: Appearance) => {
    const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', isDark);
};

const mediaQuery = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    return window.matchMedia('(prefers-color-scheme: dark)');
};

let currentAppearance: Appearance = 'system';

const handleSystemThemeChange = () => {
    applyTheme(currentAppearance);
};

export function initializeTheme() {
    mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
    const [appearance, updateAppearance] = useCookie<Appearance>('appearance', 'system', {
        useLocalStorage: true,
    });

    currentAppearance = appearance;

    const setAppearance = useCallback(
        (mode: Appearance) => {
            updateAppearance(mode);
            applyTheme(mode);
        },
        [updateAppearance],
    );

    useEffect(() => {
        applyTheme(appearance);

        return () => mediaQuery()?.removeEventListener('change', handleSystemThemeChange);
    }, [appearance]);

    return { appearance, updateAppearance: setAppearance } as const;
}
