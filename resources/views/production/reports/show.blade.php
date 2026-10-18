@extends('layouts.admin')
@section('title', 'Farm action plan #'.$report->id)
@section('content')
    @php
        $prefix = $admin ? 'admin' : 'producer';
        $writable = !$admin && $farm->status === \App\Enums\FarmStatus::Active && $farm->producerProfile->is_active;
        $journal = $report->facts['journal'];
        $adopted = $report->activities->keyBy('recommendation_index');
    @endphp
    <style>
        @media print {
            aside, body > div > header, .print-hide, a[href="#main-content"] { display: none !important; }
            body { display: block !important; background: white !important; }
            main { max-width: none !important; padding: 0 !important; }
            .surface-card { box-shadow: none; break-inside: avoid; }
        }
    </style>
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">{{ $farm->name }} / Report #{{ $report->id }}</p><h1 class="display-title text-4xl sm:text-5xl">A plan for the next step.</h1><p class="mt-4 text-sm text-stone-500">Generated {{ $report->created_at->format('d M Y, H:i') }} · {{ $report->provider }} / {{ $report->model }}</p></div><div class="print-hide flex flex-wrap gap-3"><a class="btn-secondary" href="{{ route($prefix.'.farms.reports.index', $farm->id) }}">All reports</a><button class="btn-secondary" type="button" x-data @click="window.print()">Print / save PDF</button></div></div>
    <x-card class="mb-7 border-olive-100 bg-olive-50"><p class="eyebrow text-olive-600">The perspective</p><p class="mt-4 font-display text-2xl leading-relaxed">{{ $report->advice['summary'] }}</p><p class="mt-5 text-sm leading-7 text-stone-600">{{ $report->advice['limitations'] }}</p></x-card>
    <div class="mb-7 grid gap-6 lg:grid-cols-2">
        <x-card><h2 class="font-display text-2xl">Facts behind this report</h2><dl class="mt-5 grid grid-cols-2 gap-5 text-sm">
            @foreach(['Region' => $report->facts['governorate'], 'Area' => $report->facts['area_ha'].' ha', 'Olive variety' => $report->facts['olive_variety'], 'Irrigation' => ucfirst($report->facts['irrigation_type']), 'Farming practice' => ucfirst($report->facts['farming_type']), 'Activity records included' => $journal['included_records'].' / '.$journal['matching_records']] as $label => $value)<div><dt class="text-stone-500">{{ $label }}</dt><dd class="mt-2 font-semibold">{{ $value }}</dd></div>@endforeach
        </dl><p class="mt-5 border-t border-stone-100 pt-4 text-xs leading-6 text-stone-500">Snapshot as of {{ $journal['as_of'] }}. Planned-date window: {{ $journal['window_start'] }} to {{ $journal['window_end'] }}. Up to 30 entries, newest planned dates first. Later edits are not reflected in this saved report.</p></x-card>
        <x-card><h2 class="font-display text-2xl">Information to review</h2>@if(count($report->advice['flags']))<ul class="mt-5 list-disc space-y-3 pl-5 text-sm leading-7 text-stone-600">@foreach($report->advice['flags'] as $flag)<li>{{ $flag }}</li>@endforeach</ul>@else<p class="mt-5 text-sm text-stone-500">No additional information flagged.</p>@endif<p class="mt-5 text-xs leading-6 text-stone-500">An absent journal entry is missing information, not proof that work was not performed. Declared organic practice is not certification.</p></x-card>
    </div>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow text-olive-600">Review, then decide</p><h2 class="mt-3 font-display text-3xl">Suggested next actions</h2></div><a class="print-hide text-link" href="{{ route($prefix.'.farms.activities.index', $farm->id) }}">Open activity journal →</a></div>
    <div class="space-y-5">
        @foreach($report->advice['recommendations'] as $index => $recommendation)
            <x-card>
                <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="eyebrow text-olive-600">{{ \App\Enums\FarmActivityType::from($recommendation['type'])->label() }}</p><h3 class="mt-3 font-display text-2xl">{{ $recommendation['title'] }}</h3></div><span class="role-badge">{{ ucfirst($recommendation['priority']) }} follow-up priority</span></div>
                <p class="mt-4 text-sm leading-7 text-stone-600">{{ $recommendation['reason'] }}</p>
                <p class="mt-3 text-xs leading-6 text-stone-500">
                    @if(count($recommendation['evidence_activity_ids']))
                        References recorded activity IDs: {{ implode(', ', $recommendation['evidence_activity_ids']) }}.
                    @else
                        Based on farm characteristics or information to collect; no activity is cited.
                    @endif
                </p>
                @if($adopted->has($index))
                    <p class="mt-5 rounded-xl bg-olive-50 p-4 text-sm font-medium">Added to the journal · {{ $adopted[$index]->status->label() }} · Planned {{ $adopted[$index]->planned_on->format('d M Y') }}</p>
                @elseif($writable)
                    <details class="print-hide mt-5 border-t border-stone-100 pt-5"><summary class="text-link cursor-pointer">Review and add to my plan</summary>
                        <form method="POST" action="{{ route('producer.farms.reports.adopt', [$farm->id, $report->id, $index]) }}" class="mt-5 space-y-4">
                            @csrf
                            <div class="grid gap-4 sm:grid-cols-[2fr_1fr]"><div><label for="action-title-{{ $index }}" class="form-label">Your activity title</label><input class="form-input mt-2 w-full" id="action-title-{{ $index }}" name="title" value="{{ $recommendation['title'] }}" maxlength="150" required /></div><div><label for="action-date-{{ $index }}" class="form-label">Planned date</label><input class="form-input mt-2 w-full" id="action-date-{{ $index }}" name="planned_on" type="date" value="{{ today()->toDateString() }}" min="{{ today()->toDateString() }}" max="{{ today()->addYear()->toDateString() }}" required /></div></div>
                            <div><label for="action-notes-{{ $index }}" class="form-label">Your private notes (optional)</label><textarea class="form-input mt-2 w-full rounded-xl border-stone-300 text-sm" id="action-notes-{{ $index }}" name="notes" rows="2" maxlength="2000"></textarea></div>
                            <label class="flex items-start gap-3 text-sm leading-6"><input class="mt-1" name="reviewed" type="checkbox" value="1" required /><span>I have reviewed this suggestion and chosen this activity. Local agronomic advice may be needed before carrying out work.</span></label>
                            <button class="btn-primary" type="submit">Add reviewed activity</button>
                        </form>
                    </details>
                @else<p class="mt-5 text-sm text-stone-500">Only the producer can add activities, while the farm and profile are active.</p>@endif
            </x-card>
        @endforeach
    </div>
    <details class="mt-7"><summary class="text-link cursor-pointer">View the structured activity snapshot</summary><x-card class="mt-4"><p class="text-xs leading-6 text-stone-500">Included completed irrigation: {{ $journal['completed_irrigation_water_m3'] }} m³ declared. Overdue plans in this selection: {{ $journal['overdue_plans'] }}. These totals describe selected records, not measured water consumption or environmental benefits.</p><div class="mt-4 space-y-3">@forelse($journal['activities'] as $entry)<p class="text-sm leading-6">#{{ $entry['id'] }} · {{ \App\Enums\FarmActivityType::from($entry['type'])->label() }} · {{ ucfirst($entry['status']) }} · Planned {{ $entry['planned_on'] }}@if($entry['completed_on']) · Completed {{ $entry['completed_on'] }}@endif @if($entry['water_m3'] !== null) · {{ $entry['water_m3'] }} m³ @endif</p>@empty<p class="text-sm text-stone-500">No activity records were included.</p>@endforelse</div></x-card></details>
    <x-card class="mt-7"><h2 class="font-display text-2xl">Further considerations</h2><ul class="mt-4 list-disc space-y-2 pl-5 text-sm leading-7 text-stone-600">@foreach($report->advice['suggestions'] as $suggestion)<li>{{ $suggestion }}</li>@endforeach</ul><p class="mt-5 text-xs leading-6 text-stone-500">AI advice is not a diagnosis, certificate, weather forecast or measured sustainability score. Progress means recorded task completion, not proven environmental improvement.</p></x-card>
@endsection
