import { createInertiaApp } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';

const esHttps = typeof window !== 'undefined' && window.location.protocol === 'https:';
const puertoVite = import.meta.env.VITE_REVERB_PORT;
const puerto = puertoVite
    ? Number(puertoVite)
    : esHttps
      ? 443
      : 80;

configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST || (typeof window !== 'undefined' ? window.location.hostname : 'localhost'),
    wsPort: puerto,
    wssPort: puerto,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || (esHttps ? 'https' : 'http')) === 'https',
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
