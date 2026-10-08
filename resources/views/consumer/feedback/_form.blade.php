{{-- Shared review form: embedded on the trace page (create or update) and reused on the edit page. --}}
<x-validation-errors class="mb-4" />
<form method="POST" action="{{ $feedback ? route('feedback.update', $feedback) : route('feedback.store', $productId) }}" class="space-y-5">
    @csrf @if($feedback) @method('PATCH') @endif
    <div x-data="{ rating: {{ (int) old('rating', $feedback?->rating ?? 0) }} }">
        <x-input-label value="Your rating *" />
        <input type="hidden" name="rating" :value="rating">
        <div class="mt-2 flex items-center gap-1" role="radiogroup" aria-label="Your rating from 1 to 5">
            <template x-for="star in [1,2,3,4,5]" :key="star">
                <button type="button" @click="rating = star" :aria-label="star + ' out of 5 stars'" class="text-4xl leading-none transition-transform hover:scale-110" :class="star <= rating ? 'text-gold' : 'text-stone-300'">
                    <span aria-hidden="true">★</span>
                </button>
            </template>
        </div>
        <p class="mt-2 text-sm text-stone-500"><span x-text="rating > 0 ? rating + ' / 5' : 'Tap a star to rate'"></span></p>
        <x-input-error :messages="$errors->get('rating')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="comment" value="Your tasting notes (optional)" />
        <textarea id="comment" name="comment" rows="4" maxlength="2000" placeholder="Aroma, taste, packaging, delivery…" class="mt-1 block w-full rounded-lg border-stone-300">{{ old('comment', $feedback?->comment) }}</textarea>
        <x-input-error :messages="$errors->get('comment')" class="mt-2" />
    </div>
    <x-primary-button>{{ $feedback ? 'Update my review' : 'Publish my review' }}</x-primary-button>
</form>
