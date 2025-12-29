import '../css/app.css';

import { FingerprintJSPro, FpjsProvider } from '@fingerprintjs/fingerprintjs-pro-react';
import { createInertiaApp } from '@inertiajs/react';
import * as Sentry from '@sentry/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const fingerprintApiKey = import.meta.env.VITE_FINGERPRINT_PUBLIC_KEY;
const fingerprintEndpoint = import.meta.env.VITE_FINGERPRINT_ENDPOINT || FingerprintJSPro.defaultEndpoint;
const fingerprintScriptUrlPattern = import.meta.env.VITE_FINGERPRINT_SCRIPT_URL_PATTERN || FingerprintJSPro.defaultScriptUrlPattern;

Sentry.init({
    dsn: import.meta.env.VITE_SENTRY_REACT_DSN || undefined,
    sendDefaultPii: true,
    release: import.meta.env.VITE_APP_VERSION || undefined,
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el, {
            onUncaughtError: Sentry.reactErrorHandler((error, errorInfo) => {
                console.error('Uncaught error', error, errorInfo.componentStack);
            }),
            onCaughtError: Sentry.reactErrorHandler((error, errorInfo) => {
                console.warn('Caught error', error, errorInfo.componentStack);
            }),
            onRecoverableError: Sentry.reactErrorHandler((error, errorInfo) => {
                console.warn('Recoverable error', error, errorInfo.componentStack);
            }),
        });

        const app = <App {...props} />;

        root.render(
            fingerprintApiKey ? (
                <FpjsProvider
                    loadOptions={{
                        apiKey: fingerprintApiKey,
                        endpoint: [fingerprintEndpoint],
                        scriptUrlPattern: [fingerprintScriptUrlPattern],
                    }}
                >
                    {app}
                </FpjsProvider>
            ) : (
                app
            ),
        );
    },
    progress: {
        color: '#4B5563',
    },
});

initializeTheme();
