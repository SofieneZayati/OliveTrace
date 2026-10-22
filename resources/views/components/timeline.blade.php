@if($histories ?? false)
<div class="mt-6 bg-white shadow-sm rounded-lg p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Timeline</h2>
    <ol class="relative border-l border-gray-200">
        @foreach($histories as $history)
            <li class="mb-6 ml-4">
                <div class="absolute w-3 h-3 bg-indigo-600 rounded-full -left-1.5 border border-white"></div>
                <time class="mb-1 text-sm font-normal leading-none text-gray-400">{{ $history->created_at?->format('d M Y H:i') }}</time>
                <h3 class="text-sm font-semibold text-gray-900">{{ class_basename($history->trackable_type) }}: {{ $history->from_status ? $history->from_status.' → '.$history->to_status : $history->to_status }}</h3>
                @if($history->reason)
                    <p class="text-sm font-normal text-gray-500">{{ $history->reason }}</p>
                @endif
                @if($history->changer)
                    <p class="text-xs text-gray-400">by {{ $history->changer->name }}</p>
                @endif
            </li>
        @endforeach
    </ol>
</div>
@endif
