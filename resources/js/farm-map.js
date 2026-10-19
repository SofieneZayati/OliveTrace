let leafletPromise;

function leaflet() {
    if (!leafletPromise) {
        leafletPromise = Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')])
            .then(([module]) => module.default ?? module)
            .catch((error) => {
                leafletPromise = null;
                throw error;
            });
    }
    return leafletPromise;
}

export function coordinatePair(lat, lng) {
    if (lat === null || lng === null || lat === undefined || lng === undefined
        || String(lat).trim() === '' || String(lng).trim() === '') return null;
    const latitude = Number(lat);
    const longitude = Number(lng);
    return Number.isFinite(latitude) && Number.isFinite(longitude)
        && latitude >= -90 && latitude <= 90 && longitude >= -180 && longitude <= 180
        ? [latitude, longitude] : null;
}

export function farmMap(options) {
    // Leaflet instances stay outside Alpine's reactive proxy.
    let L;
    let map;
    let selectedMarker;
    let resizeObserver;
    let latitudeInput;
    let longitudeInput;
    let fieldListener;
    let writingInputs = false;
    let destroyed = false;
    const points = (options.points ?? []).filter((point) => coordinatePair(point.lat, point.lng));

    return {
        loaded: false,
        loading: false,
        status: '',
        error: '',

        init() {
            this.$nextTick(() => {
                if (destroyed) return;
                if (options.picker) {
                    latitudeInput = this.$refs.latitude;
                    longitudeInput = this.$refs.longitude;
                    fieldListener = () => this.syncFields();
                    [latitudeInput, longitudeInput].forEach((field) => {
                        field?.addEventListener('input', fieldListener);
                        field?.addEventListener('change', fieldListener);
                    });
                    this.syncFields();
                } else {
                    this.status = points.length ? `${points.length} saved farm location${points.length === 1 ? '' : 's'}.` : 'No saved farm coordinates to display.';
                }
            });
        },

        selected() {
            return coordinatePair(latitudeInput?.value, longitudeInput?.value);
        },

        async load() {
            if (this.loaded || this.loading) return;
            this.loading = true;
            this.error = '';
            try {
                L = await leaflet();
                if (destroyed) return;
                this.loaded = true;
                await this.$nextTick();
                // Alpine may reveal x-show elements on the next animation frame.
                await new Promise((resolve) => requestAnimationFrame(resolve));
                if (destroyed) return;
                map = L.map(this.$refs.canvas, {
                    scrollWheelZoom: false, doubleClickZoom: !options.picker,
                    minZoom: 3, maxZoom: options.maxZoom, worldCopyJump: true,
                }).setView(options.center, options.zoom);
                L.tileLayer(options.tiles, { maxZoom: options.maxZoom, attribution: options.attribution })
                    .on('tileerror', () => {
                        this.error = 'Some map tiles could not load. Your saved data is unchanged; coordinate entry and farm links remain available.';
                    }).addTo(map);
                if (options.picker) {
                    map.on('click', (event) => this.choose(event.latlng));
                    this.syncFields();
                } else {
                    points.forEach((point) => {
                        const popup = document.createElement('div');
                        const title = document.createElement('strong');
                        title.textContent = point.name;
                        const detail = document.createElement('p');
                        detail.textContent = `${point.region} · ${point.status}`;
                        const link = document.createElement('a');
                        link.textContent = 'Open farm →';
                        // Routes are generated server-side; keep navigation on this application.
                        const url = new URL(point.url, window.location.origin);
                        if (url.origin === window.location.origin) link.href = url.href;
                        popup.append(title, detail, link);
                        L.marker([point.lat, point.lng], { icon: this.icon(), title: point.name, alt: 'Farm location' })
                            .addTo(map).bindPopup(popup);
                    });
                }
                this.fit();
                let firstSize = true;
                resizeObserver = new ResizeObserver(() => {
                    if (!map) return;
                    map.invalidateSize({ pan: false });
                    if (firstSize) {
                        firstSize = false;
                        this.fit();
                    }
                });
                resizeObserver.observe(this.$refs.canvas);
            } catch {
                map?.remove();
                map = null;
                selectedMarker = null;
                this.loaded = false;
                this.error = 'The interactive map could not load. You can still enter coordinates manually and use the farm pages. Try loading it again.';
            } finally {
                this.loading = false;
            }
        },

        icon() {
            return L.divIcon({
                className: 'farm-map-marker', html: '<span aria-hidden="true"></span>',
                iconSize: [28, 36], iconAnchor: [14, 34], popupAnchor: [0, -32],
            });
        },

        syncFields() {
            if (writingInputs) return;
            const pair = this.selected();
            this.status = pair
                ? `Selected: ${pair[0].toFixed(7)}, ${pair[1].toFixed(7)}. Save the farm to keep changes.`
                : 'Provide both valid coordinates, or leave both empty. No location is assumed.';
            if (!map) return;
            if (!pair) {
                selectedMarker?.remove();
                selectedMarker = null;
            } else if (selectedMarker) {
                selectedMarker.setLatLng(pair);
            } else {
                selectedMarker = L.marker(pair, { icon: this.icon(), draggable: true, title: 'Drag to change farm location', alt: 'Selected farm location' })
                    .addTo(map).on('dragend', () => this.choose(selectedMarker.getLatLng()));
            }
        },

        choose(latlng) {
            const wrapped = latlng.wrap();
            const pair = coordinatePair(wrapped.lat, wrapped.lng);
            if (!pair || !latitudeInput || !longitudeInput) return;
            writingInputs = true;
            latitudeInput.value = pair[0].toFixed(7);
            longitudeInput.value = pair[1].toFixed(7);
            [latitudeInput, longitudeInput].forEach((field) => field.dispatchEvent(new Event('input', { bubbles: true })));
            writingInputs = false;
            this.syncFields();
        },

        clear() {
            if (!options.picker || !latitudeInput || !longitudeInput) return;
            writingInputs = true;
            [latitudeInput, longitudeInput].forEach((field) => {
                field.value = '';
                field.dispatchEvent(new Event('input', { bubbles: true }));
            });
            writingInputs = false;
            this.syncFields();
        },

        fit() {
            if (!map) return;
            const coordinates = options.picker ? (this.selected() ? [this.selected()] : []) : points.map((point) => [point.lat, point.lng]);
            if (coordinates.length === 1) map.setView(coordinates[0], 13);
            else if (coordinates.length > 1) map.fitBounds(coordinates, { padding: [35, 35], maxZoom: 13 });
            else this.showTunisia();
        },

        showTunisia() {
            map?.setView(options.center, options.zoom);
        },

        destroy() {
            destroyed = true;
            resizeObserver?.disconnect();
            [latitudeInput, longitudeInput].forEach((field) => {
                field?.removeEventListener('input', fieldListener);
                field?.removeEventListener('change', fieldListener);
            });
            map?.remove();
            map = null;
        },
    };
}
