import { createInertiaApp } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';

declare global {
    interface Window {
        __AMENHOOT_ECHO__?: {
            key?: string;
            host?: string;
            port?: number;
            scheme?: string;
        };
    }
}

const eco = typeof window !== 'undefined' ? (window.__AMENHOOT_ECHO__ ?? {}) : {};
const esHttps =
    (eco.scheme ?? import.meta.env.VITE_REVERB_SCHEME ?? (typeof window !== 'undefined' && window.location.protocol === 'https:' ? 'https' : 'http')) ===
    'https';
const puertoVite = eco.port ?? (import.meta.env.VITE_REVERB_PORT ? Number(import.meta.env.VITE_REVERB_PORT) : undefined);
const puerto = puertoVite ?? (esHttps ? 443 : 80);

configureEcho({
    broadcaster: 'reverb',
    key: eco.key ?? import.meta.env.VITE_REVERB_APP_KEY,
    wsHost:
        eco.host ??
        import.meta.env.VITE_REVERB_HOST ??
        (typeof window !== 'undefined' ? window.location.hostname : 'localhost'),
    wsPort: puerto,
    wssPort: puerto,
    forceTLS: esHttps,
    enabledTransports: ['ws', 'wss'],
});

const appName = import.meta.env.VITE_APP_NAME || 'Amenhoot';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});
