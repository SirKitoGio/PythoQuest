import Alpine from 'alpinejs';
import './map';
import photoCapture from './camera';

window.Alpine = Alpine;

/**
 * Live notification bell: starts from the server-rendered unread count and increments as
 * review outcomes and escalations arrive over the user's private Reverb channel.
 */
Alpine.data('liveNotifications', (userId, unread) => ({
    unread,
    toast: null,
    init() {
        window.Echo?.private(`App.Models.User.${userId}`).notification((notification) => {
            this.unread++;
            this.toast = notification;
            setTimeout(() => (this.toast = null), 6000);
        });
    },
}));

Alpine.data('photoCapture', photoCapture);

/**
 * Saves a plant profile for offline reading through the service worker.
 */
Alpine.data('offlineSave', (url) => ({
    label: 'Save for offline',
    async save() {
        const registration = await navigator.serviceWorker?.getRegistration();

        if (! registration?.active) {
            this.label = 'Offline saving unavailable in this browser';

            return;
        }

        registration.active.postMessage({ type: 'save-profile', url });
        this.label = 'Saved for offline ✓';
    },
}));

/**
 * Type-ahead over the full plant catalogue using the public API.
 */
Alpine.data('plantPicker', (initial = null) => ({
    query: '',
    results: [],
    selected: initial,
    async search() {
        if (this.query.trim().length < 2) {
            this.results = [];

            return;
        }

        const params = new URLSearchParams({ q: this.query, per_page: 8, suggest: 1 });
        const response = await fetch(`/api/v1/plants?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        this.results = (await response.json()).data;
    },
    choose(plant) {
        this.selected = { id: plant.id, label: `${plant.common_name} (${plant.scientific_name})` };
        this.results = [];
        this.query = '';
        this.$dispatch('plant-chosen', this.selected);
    },
}));

navigator.serviceWorker?.register('/sw.js').catch(() => {
    // Service workers are unavailable (e.g. insecure origin or embedded webview); offline saving is disabled.
});

Alpine.start();

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
