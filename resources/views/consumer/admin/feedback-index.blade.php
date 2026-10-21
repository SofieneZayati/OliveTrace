@extends('layouts.admin')
@section('title', 'Consumer reviews')
@section('content')
    <div class="mb-8"><p class="eyebrow mb-4 text-olive-600">Post-purchase trust</p><h1 class="display-title text-4xl">Moderate reviews.</h1><p class="mt-4 text-sm text-stone-500">{{ $feedback->total() }} review(s). Hide inappropriate content; never edit a consumer's words.</p></div>
    <x-card class="mb-7"><form method="GET" class="flex flex-wrap items-end gap-4">
        <div><x-input-label for="status" value="Visibility" /><select id="status" name="status" class="mt-1 block w-48 rounded-lg border-stone-300"><option value="">All</option>@foreach(\App\Enums\Consumer\FeedbackStatus::cases() as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select></div>
        <div class="flex items-center gap-3"><button class="btn-primary" type="submit">Filter</button><a class="text-link" href="{{ route('admin.consumer.feedback.index') }}">Reset</a></div>
    </form></x-card>
    <div class="space-y-5">
        @forelse($feedback as $item)
            <x-card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="star" class="h-4 w-4 text-olive-600" />{{ $item->rating }} / 5 <span class="font-normal text-stone-500">· {{ $item->consumer?->name }} ({{ $item->consumer?->email }}) · {{ $item->product?->name ?? 'product #'.$item->oil_product_id }}</span></p>
                    <span class="role-badge">{{ $item->status->label() }}</span>
                </div>
                @if($item->comment)<p class="mt-3 text-sm leading-6">{{ $item->comment }}</p>@endif
                <p class="mt-3 text-xs text-stone-500">Assistant suggestion: {{ $item->ai_category?->label() ?? '—' }} · {{ $item->ai_sentiment?->label() ?? '—' }} · {{ $item->ai_priority?->label() ?? '—' }} priority (advisory only).</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('admin.consumer.feedback.update', $item) }}" class="flex items-center gap-3">@csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $item->status === \App\Enums\Consumer\FeedbackStatus::Visible ? 'hidden' : 'visible' }}">
                        <button type="submit" class="btn-secondary py-2">{{ $item->status === \App\Enums\Consumer\FeedbackStatus::Visible ? 'Hide review' : 'Show review' }}</button>
                    </form>
                    <button type="button" class="btn-primary text-sm" x-data="{ aiLoading: false, aiResponse: '' }" @click="loadAiResponse({{ $item->id }})">
                        <span x-show="!aiLoading">Réponse IA</span>
                        <span x-show="aiLoading" class="flex items-center gap-1"><svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>Génération...</span>
                    </button>
                </div>
                <!-- AI Response Modal -->
                <div x-show="aiResponse" x-transition:enter="transition ease-out duration-100" x-transition:leave="transition ease-in duration-75" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" x-cloak>
                    <div class="bg-white rounded-xl p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-display text-xl">Réponse IA suggérée</h3>
                            <button @click="aiResponse = ''" class="text-stone-400 hover:text-stone-600"><x-icon name="cross" class="h-5 w-5" /></button>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-stone-700 mb-1">Ton</label>
                            <select x-model="selectedTone" class="w-full rounded-lg border-stone-300 p-2" @change="loadAiResponse({{ $item->id }})">
                                <option value="professional">Professionnel</option>
                                <option value="empathetic">Empathique</option>
                                <option value="formal">Formel</option>
                                <option value="friendly">Amical</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-stone-700 mb-1">Réponse suggérée</label>
                            <textarea x-model="editableResponse" rows="6" class="w-full rounded-lg border-stone-300 p-3 resize-y" placeholder="La réponse IA apparaîtra ici..."></textarea>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button @click="saveAiResponse({{ $item->id }})" class="btn-primary" :disabled="saving">Enregistrer comme réponse admin</button>
                            <button @click="aiResponse = ''" class="btn-secondary">Annuler</button>
                        </div>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card><p class="text-sm text-stone-500">No reviews to moderate.</p></x-card>
        @endforelse
    </div>
    <div class="mt-8">{{ $feedback->links() }}</div>
@push('scripts')
<script>
    function loadAiResponse(feedbackId) {
        const tone = document.querySelector('[x-data] select[name="tone"]')?.value || 'professional';
        this.aiLoading = true;
        fetch(`/admin/consumer/feedback/${feedbackId}/ai-response`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ tone: this.selectedTone || 'professional' })
        })
        .then(r => r.json())
        .then(data => {
            this.aiResponse = true;
            this.selectedTone = tone;
            this.editableResponse = data.suggestion?.suggested_response || data.suggestion || data.response || data.message || 'Erreur lors de la génération';
            this.aiLoading = false;
        })
        .catch(() => {
            this.aiLoading = false;
            alert('Erreur lors de la génération de la réponse IA');
        });
    }

    function saveAiResponse(feedbackId) {
        this.saving = true;
        fetch(`/admin/consumer/feedback/${feedbackId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                admin_response: this.editableResponse,
                status: 'visible'
            })
        })
        .then(r => {
            if (r.ok) {
                alert('Réponse enregistrée avec succès');
                this.aiResponse = false;
                location.reload();
            }
        })
        .catch(() => alert('Erreur lors de l\'enregistrement'))
        .finally(() => this.saving = false);
    }
</script>
@endpush
