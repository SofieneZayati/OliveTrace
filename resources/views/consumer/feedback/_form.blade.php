{{-- Shared review form: embedded on the trace page (create or update) and reused on the edit page. --}}
<x-validation-errors class="mb-4" />
<form method="POST" action="{{ $feedback ? route('feedback.update', $feedback) : route('feedback.store', $productId) }}" class="space-y-5">
    @csrf @if($feedback) @method('PATCH') @endif
    <div>
        <x-input-label for="rating" value="Your rating (1–5)" />
        <select id="rating" name="rating" required class="mt-1 block w-40 rounded-lg border-stone-300">
            @foreach([5, 4, 3, 2, 1] as $value)
                <option value="{{ $value }}" @selected((int) old('rating', $feedback?->rating ?? 0) === $value)>{{ $value }} / 5</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('rating')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="comment" value="Your tasting notes (optional)" />
        <textarea id="comment" name="comment" rows="4" maxlength="2000" placeholder="Aroma, taste, packaging, delivery…" class="mt-1 block w-full rounded-lg border-stone-300">{{ old('comment', $feedback?->comment) }}</textarea>
        <x-input-error :messages="$errors->get('comment')" class="mt-2" />
    </div>
    <x-primary-button>{{ $feedback ? 'Update my review' : 'Publish my review' }}</x-primary-button>
</form>
