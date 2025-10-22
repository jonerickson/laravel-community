import '../css/app.css';

import { FingerprintJSPro, FpjsProvider } from '@fingerprintjs/fingerprintjs-pro-react';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const fingerprintApiKey = import.meta.env.VITE_FINGERPRINT_PUBLIC_KEY;

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <FpjsProvider
                loadOptions={{
                    apiKey: fingerprintApiKey,
                    endpoint: [FingerprintJSPro.defaultEndpoint],
                    scriptUrlPattern: [FingerprintJSPro.defaultScriptUrlPattern],
                }}
            >
                <App {...props} />
            </FpjsProvider>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
