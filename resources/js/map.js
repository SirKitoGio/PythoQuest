import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const JRU_CAMPUS = [14.5869, 121.036];

const STATUS_COLOURS = {
    trusted: '#2A7C13',
    pending: '#B45309',
    'needs correction': '#C2410C',
    'sensitive-restricted': '#7C3AED',
};

/**
 * Renders sighting points. Exact pins are solid markers; generalized points are drawn as
 * a ~1 km shaded cell so the map never implies more precision than the record allows.
 */
export function sightingMap(element, { points = [], sourceUrl = null, center = JRU_CAMPUS, zoom = 15 } = {}) {
    const map = L.map(element, { scrollWheelZoom: false }).setView(center, zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const layer = L.layerGroup().addTo(map);

    const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => `&#${c.charCodeAt(0)};`);

    const render = (features) => {
        layer.clearLayers();
        const bounds = [];

        features.forEach((point) => {
            const colour = STATUS_COLOURS[point.status] ?? '#2A7C13';
            const latLng = [point.latitude, point.longitude];
            const popup = `<strong>${escape(point.common_name ?? '')}</strong><br>
                <span style="text-transform:capitalize">${escape(point.status)}</span> &middot; ${escape(point.observed_on)}<br>
                ${point.precision === 'exact' ? escape(point.location_name ?? 'Exact pin') : 'Generalized area (~1 km) to protect this record'}`;

            if (point.precision === 'exact') {
                L.circleMarker(latLng, { radius: 8, color: '#fff', weight: 2, fillColor: colour, fillOpacity: 0.95 }).bindPopup(popup).addTo(layer);
            } else {
                L.rectangle([[point.latitude - 0.005, point.longitude - 0.005], [point.latitude + 0.005, point.longitude + 0.005]], {
                    color: colour, weight: 1, dashArray: '4', fillOpacity: 0.12,
                }).bindPopup(popup).addTo(layer);
            }

            bounds.push(latLng);
        });

        if (bounds.length) {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
        }
    };

    const load = async (params = {}) => {
        const query = new URLSearchParams(Object.entries(params).filter(([, value]) => value)).toString();
        const response = await fetch(`${sourceUrl}${query ? `?${query}` : ''}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const geojson = await response.json();

        render(geojson.features.map((feature) => ({
            ...feature.properties,
            latitude: feature.geometry.coordinates[1],
            longitude: feature.geometry.coordinates[0],
        })));

        return geojson.features.length;
    };

    if (sourceUrl) {
        load();
    } else {
        render(points);
    }

    return { map, load };
}

/**
 * A draggable pin for confirming or adjusting a GPS reading (FR-08).
 */
export function pinPicker(element, { latitude = null, longitude = null, onMove = () => {} } = {}) {
    const hasPosition = latitude !== null && longitude !== null;
    const map = L.map(element, { scrollWheelZoom: false, tap: true }).setView(hasPosition ? [latitude, longitude] : JRU_CAMPUS, hasPosition ? 18 : 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20,
        maxNativeZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const icon = L.divIcon({
        className: '',
        html: '<div style="width:28px;height:28px;margin:-28px 0 0 -14px;border-radius:50% 50% 50% 0;background:#2A7C13;border:3px solid #FFF8CF;transform:rotate(-45deg);box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>',
    });
    const marker = L.marker(map.getCenter(), { draggable: true, icon, opacity: hasPosition ? 1 : 0 }).addTo(map);
    const emit = (latLng) => onMove(Number(latLng.lat.toFixed(7)), Number(latLng.lng.toFixed(7)));

    marker.on('dragend', () => emit(marker.getLatLng()));
    map.on('click', (event) => {
        marker.setLatLng(event.latlng).setOpacity(1);
        emit(event.latlng);
    });

    return {
        setPosition(lat, lng) {
            marker.setLatLng([lat, lng]).setOpacity(1);
            map.setView([lat, lng], 18);
        },
    };
}

window.sightingMap = sightingMap;
window.pinPicker = pinPicker;
