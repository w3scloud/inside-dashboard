<template>
    <div ref="mapContainer" class="h-full w-full rounded-lg overflow-hidden"></div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const mapContainer = ref(null);
let mapInstance = null;
let markersLayer = null;

const points = [
    { lat: 36.77, lng: -119.42, code: 'CA', value: 144 },
    { lat: 31.0, lng: -100.0, code: 'TX', value: 120 },
    { lat: 42.9, lng: -75.0, code: 'NY', value: 104 },
    { lat: 27.8, lng: -81.7, code: 'FL', value: 97 },
    { lat: 47.5, lng: -120.5, code: 'WA', value: 88 },
    { lat: 40.0, lng: -89.0, code: 'IL', value: 76 },
    { lat: 39.0, lng: -105.5, code: 'CO', value: 64 },
    { lat: 34.0, lng: -111.0, code: 'AZ', value: 56 },
];

onMounted(() => {
    if (!mapContainer.value) return;

    mapInstance = L.map(mapContainer.value, {
        zoomControl: true,
        attributionControl: false,
    }).setView([39.5, -98.35], 4);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 10,
    }).addTo(mapInstance);

    markersLayer = L.layerGroup().addTo(mapInstance);

    points.forEach((p) => {
        const radius = 10 + (p.value / 160) * 20;
        const marker = L.circleMarker([p.lat, p.lng], {
            radius,
            color: '#4f46e5',
            weight: 1,
            fillColor: '#93c5fd',
            fillOpacity: 0.6,
        }).bindTooltip(`${p.code}: ${p.value}`, {
            permanent: true,
            direction: 'center',
            className: 'draft-billing-label',
        });

        marker.addTo(markersLayer);
    });
});

onUnmounted(() => {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
    }
});
</script>

<style scoped>
.draft-billing-label {
    background: transparent;
    border: none;
    box-shadow: none;
    color: #1f2933;
    font-size: 10px;
    font-weight: 600;
}
</style>

