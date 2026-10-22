@extends('layouts.front')
@section('title', 'Tracer votre bouteille')
@section('content')
    <div class="mx-auto max-w-2xl text-center">
        <p class="eyebrow mb-4 text-olive-600">Traçabilité consommateur</p>
        <h1 class="display-title mb-4 text-4xl sm:text-5xl">Tracez votre bouteille.</h1>
        <p class="mb-8 text-sm leading-7 text-stone-500">Scannez le QR code sur votre bouteille ou entrez le code produit / numéro de lot pour consulter l'historique complet.</p>

        @if(session('error'))
            <div class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        {{-- Search form --}}
        <x-card class="mb-8">
            <x-validation-errors class="mb-4" />
            <form method="GET" action="{{ route('trace.search.query') }}" class="flex flex-col items-center gap-4 sm:flex-row">
                <div class="flex-1 w-full">
                    <x-text-input id="q" name="q" type="text" class="w-full text-center sm:text-left" :value="old('q')" placeholder="Code produit, slug ou numéro de lot…" required />
                </div>
                <x-primary-button class="shrink-0">🔍 Rechercher</x-primary-button>
            </form>
        </x-card>

        {{-- QR Scanner --}}
        <x-card>
            <h2 class="font-display mb-4 text-2xl">📷 Scanner un QR code</h2>
            <p class="mb-4 text-sm text-stone-500">Autorisez l'accès à la caméra pour scanner le QR code de votre bouteille.</p>

            <div x-data="qrScanner()">
                <div id="qr-reader" class="mx-auto mb-4 overflow-hidden rounded-xl" style="max-width: 400px;"></div>

                <div class="flex flex-wrap items-center justify-center gap-3">
                    <button @click="startScan()" x-show="!scanning" class="btn-primary">Démarrer le scanner</button>
                    <button @click="stopScan()" x-show="scanning" class="btn-secondary">Arrêter le scanner</button>
                </div>

                <template x-if="result">
                    <div class="mt-4 rounded-xl bg-olive-50 p-4">
                        <p class="text-sm font-semibold text-olive-800">QR détecté :</p>
                        <p class="mt-1 text-sm text-olive-600" x-text="result"></p>
                        <p class="mt-2 text-sm">Redirection en cours…</p>
                    </div>
                </template>

                <template x-if="error">
                    <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-700" x-text="error"></div>
                </template>
            </div>
        </x-card>
    </div>

    {{-- html5-qrcode library CDN --}}
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" integrity="sha384-c9d8RFSL+u3exBOJ4Yp3HUJXS4znl9f+z66d1y54ig+ea249SpqR+w1wyvXz/lk+" crossorigin="anonymous"></script>
    <script>
        function qrScanner() {
            return {
                scanning: false,
                result: null,
                error: null,
                scanner: null,

                init() {
                    if (typeof Html5Qrcode !== 'undefined') {
                        this.scanner = new Html5Qrcode('qr-reader');
                    }
                },

                startScan() {
                    if (!this.scanner) {
                        this.error = 'Le scanner est indisponible. Utilisez la recherche manuelle.';
                        return;
                    }
                    this.error = null;
                    this.result = null;
                    this.scanning = true;

                    this.scanner.start(
                        { facingMode: 'environment' },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        (decodedText) => {
                            this.result = decodedText;
                            this.stopScan();

                            // Extract slug from URL or use raw text
                            let slug = decodedText;
                            try {
                                const url = new URL(decodedText);
                                const parts = url.pathname.split('/').filter(Boolean);
                                if (parts.length >= 2 && parts[parts.length - 2] === 'trace') {
                                    slug = parts[parts.length - 1];
                                }
                            } catch (e) {
                                // Not a URL, use as-is
                            }

                            setTimeout(() => {
                                window.location.href = @js(route('trace.search.query')) + '?q=' + encodeURIComponent(slug);
                            }, 800);
                        },
                        () => {} // ignore scan failures
                    ).catch((err) => {
                        this.scanning = false;
                        this.error = 'Impossible d\'accéder à la caméra. Vérifiez les permissions ou utilisez la recherche manuelle.';
                        console.error('QR scanner error:', err);
                    });
                },

                stopScan() {
                    this.scanning = false;
                    if (this.scanner && this.scanner.isScanning) {
                        this.scanner.stop().catch(() => {});
                    }
                }
            };
        }
    </script>
@endsection
