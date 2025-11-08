import { useCallback } from 'react';
import { useCookie } from './use-cookie';

export type LayoutType = 'sidebar' | 'header';

export function useLayout() {
    const [layout, updateLayout] = useCookie<LayoutType>('layout', 'header', {
        useLocalStorage: true,
    });

    const setLayout = useCallback(
        (mode: LayoutType) => {
            updateLayout(mode);
        },
        [updateLayout],
    );

    return { layout, updateLayout: setLayout } as const;
}
