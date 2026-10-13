import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Connection settings come from the page at runtime so one build works on any domain;
// the VITE_ values remain as a fallback for local development.
const runtime = JSON.parse(document.querySelector('meta[name="reverb-config"]')?.content || 'null');

if (runtime?.key || import.meta.env.VITE_REVERB_APP_KEY) {
    const scheme = runtime?.scheme ?? import.meta.env.VITE_REVERB_SCHEME ?? 'https';
    const port = runtime?.port ?? import.meta.env.VITE_REVERB_PORT;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: runtime?.key ?? import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: runtime?.host ?? import.meta.env.VITE_REVERB_HOST,
        wsPort: port ?? 80,
        wssPort: port ?? 443,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
