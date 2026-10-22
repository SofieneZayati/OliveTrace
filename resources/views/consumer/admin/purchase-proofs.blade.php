@extends('layouts.admin')
@section('title', 'Purchase proofs')
@section('content')
    <h1 class="display-title mb-4 text-4xl">Review purchase proofs.</h1>
    <p class="mb-8 text-sm text-stone-500">Check the document and product before approving a verified-purchase badge. Uploading a file alone does not prove a purchase.</p>
    <x-validation-errors class="mb-5" />
    <div class="space-y-5">
        @forelse($purchases as $purchase)
            <x-card>
                <h2 class="font-display text-2xl">{{ $purchase->product?->name }}</h2>
                <p class="mt-2 text-sm text-stone-500">{{ $purchase->consumer?->name }} · {{ $purchase->proof_type }} · {{ $purchase->approved_at ? 'Approved' : 'Awaiting review' }}</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a class="btn-secondary" href="{{ route('products.proof.show', $purchase) }}">Download private proof</a>
                    @unless($purchase->approved_at)
                        <form method="POST" action="{{ route('admin.consumer.proofs.approve', $purchase) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="proof_version" value="{{ hash('sha256', (string) $purchase->proof_path) }}">
                            <x-primary-button>Approve reviewed proof</x-primary-button>
                        </form>
                    @endunless
                </div>
            </x-card>
        @empty
            <x-card>No purchase proofs submitted.</x-card>
        @endforelse
    </div>
    <div class="mt-8">{{ $purchases->links() }}</div>
@endsection
