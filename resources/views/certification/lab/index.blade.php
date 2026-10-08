<x-app-layout title="Laboratory requests">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-olive-800 leading-tight">
            {{ __('Laboratory Dashboard - Certification Requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-5">
                <div>
                    <h1 class="display-title text-3xl sm:text-4xl text-olive-800">Pending & Processed Requests</h1>
                    <p class="mt-2 text-sm text-stone-500">Review producer requests and issue quality certificates.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-50 text-green-700 border border-green-200">
                    <div class="flex items-center gap-3">
                        <x-icon name="check" class="h-5 w-5" />
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <x-card class="overflow-hidden p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-stone-600">
                        <thead class="bg-olive-50 text-olive-800 border-b border-olive-100">
                            <tr>
                                <th class="py-4 px-6 font-semibold">ID</th>
                                <th class="py-4 px-6 font-semibold">Producer</th>
                                <th class="py-4 px-6 font-semibold">Oil Lot</th>
                                <th class="py-4 px-6 font-semibold">Requested At</th>
                                <th class="py-4 px-6 font-semibold">Status</th>
                                <th class="py-4 px-6 font-semibold text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-olive-100">
                            @forelse($requests as $req)
                                <tr class="hover:bg-olive-50/50 transition-colors">
                                    <td class="py-4 px-6 font-medium text-stone-800">#{{ $req->id }}</td>
                                    <td class="py-4 px-6">{{ $req->producer->name }}</td>
                                    <td class="py-4 px-6"><span class="bg-stone-100 text-stone-700 px-2 py-1 rounded text-xs font-mono">{{ $req->oilLot->lot_number }}</span></td>
                                    <td class="py-4 px-6">{{ $req->requested_at->format('M d, Y') }}</td>
                                    <td class="py-4 px-6">
                                        @if($req->status === 'approved')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span> Approved
                                            </span>
                                        @elseif($req->status === 'rejected')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span> Rejected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                                <span class="h-1.5 w-1.5 rounded-full bg-yellow-500"></span> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if($req->status === 'pending')
                                                <a href="{{ route('lab.requests.show', $req) }}" class="btn-primary text-xs py-1.5 px-3">
                                                    Analyze <x-icon name="arrow" class="h-3 w-3 inline ml-1" />
                                                </a>
                                            @else
                                                <a href="{{ route('lab.requests.show', $req) }}" class="btn-secondary text-xs py-1.5 px-3">
                                                    Details
                                                </a>
                                                @if($req->status === 'approved' && $req->certificate)
                                                    <a href="{{ route('certificates.show', $req->certificate->certificate_number) }}" target="_blank" class="text-link text-xs inline-flex items-center gap-1 font-medium">
                                                        <x-icon name="document" class="h-3.5 w-3.5" /> View Certificate
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-stone-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <x-icon name="document" class="h-12 w-12 text-stone-300 mb-3" />
                                            <p class="text-lg font-medium text-stone-600">No requests found</p>
                                            <p class="text-sm">There are no certification requests at the moment.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
