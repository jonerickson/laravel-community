import { useCallback, useEffect, useState } from 'react';

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const getCookie = (name: string): string | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);

    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || null;
    }

    return null;
};

export function useCookie<T extends string>(
    cookieName: string,
    defaultValue: T,
    options?: {
        days?: number;
        useLocalStorage?: boolean;
    },
) {
    const { days = 365, useLocalStorage = false } = options || {};
    const [value, setValue] = useState<T>(defaultValue);

    const updateValue = useCallback(
        (newValue: T) => {
            setValue(newValue);

            if (useLocalStorage && typeof localStorage !== 'undefined') {
                localStorage.setItem(cookieName, newValue);
            }

            setCookie(cookieName, newValue, days);
        },
        [cookieName, days, useLocalStorage],
    );

    useEffect(() => {
        let savedValue: string | null = null;

        if (useLocalStorage && typeof localStorage !== 'undefined') {
            savedValue = localStorage.getItem(cookieName);
        }

        if (!savedValue) {
            savedValue = getCookie(cookieName);
        }

        if (savedValue) {
            setValue(savedValue as T);
        }
    }, [cookieName, useLocalStorage]);

    return [value, updateValue] as const;
}
