@php
    $dayItems = [];
    foreach ($appointments as $appointment) {
        if (in_array($appointment['status'], ['cancelled', 'no_show'])) {
            continue;
        }
        foreach ($appointment['items'] as $item) {
            $start = \Carbon\CarbonImmutable::parse($item['startTime']);
            if (!$start->isSameDay($day)) {
                continue;
            }
            $dayItems[] = ['appointment' => $appointment, 'item' => $item, 'start' => $start];
        }
    }
    usort($dayItems, fn ($a, $b) => $a['start']->getTimestamp() <=> $b['start']->getTimestamp());
    $columnEnds = [];
    foreach ($dayItems as &$entry) {
        $column = 0;
        while (isset($columnEnds[$column]) && $columnEnds[$column] > $entry['start']->getTimestamp()) {
            $column++;
        }
        $entry['column'] = $column;
        $columnEnds[$column] = $entry['start']->addMinutes($entry['item']['durationSnapshot'])->getTimestamp();
    }
    unset($entry);
    $columns = max(1, count($columnEnds));
@endphp
<div class="calendar-day" data-day="{{ $day->format('Y-m-d') }}" data-selected="{{ $day->isSameDay($date) ? 'true' : 'false' }}" style="height: {{ $calendarHeight }}px">
    @if($day->isSameDay($today))<div class="pointer-events-none absolute inset-0 bg-teal-600/[0.03]"></div>@endif
    @foreach($professionals as $professional)
        @php
            $intervals = collect(collect($availabilities)->first(fn ($availability) => $availability['professionalId'] === $professional['id'] && $availability['dayOfWeek'] === $day->dayOfWeek)['intervals'] ?? [])->sortBy('start');
            $cursor = $calendarStartMinutes;
            $gaps = [];
            foreach ($intervals as $interval) {
                [$h, $m] = explode(':', $interval['start']);
                $from = max($calendarStartMinutes, ((int) $h * 60) + (int) $m);
                [$h, $m] = explode(':', $interval['end']);
                $to = min($calendarEndMinutes, ((int) $h * 60) + (int) $m);
                if ($to <= $calendarStartMinutes || $from >= $calendarEndMinutes) {
                    continue;
                }
                if ($from > $cursor) {
                    $gaps[] = [$cursor, $from];
                }
                $cursor = max($cursor, $to);
            }
            if ($cursor < $calendarEndMinutes) {
                $gaps[] = [$cursor, $calendarEndMinutes];
            }
        @endphp
        @foreach($gaps as [$from, $to])<div hidden data-unavailable="{{ $professional['id'] }}" class="calendar-unavailable" style="top: {{ ($from - $calendarStartMinutes) / 60 * 64 }}px; height: {{ ($to - $from) / 60 * 64 }}px"></div>@endforeach
    @endforeach
    @foreach($blocks as $block)
        @php
            $blockStart = \Carbon\CarbonImmutable::parse($block['startTime']);
            $blockEnd = \Carbon\CarbonImmutable::parse($block['endTime']);
            $calendarStart = $day->startOfDay()->addMinutes($calendarStartMinutes);
            $calendarEnd = $day->startOfDay()->addMinutes($calendarEndMinutes);
            $visibleStart = $blockStart->greaterThan($calendarStart) ? $blockStart : $calendarStart;
            $visibleEnd = $blockEnd->lessThan($calendarEnd) ? $blockEnd : $calendarEnd;
        @endphp
        @if($visibleStart->lessThan($visibleEnd) && $blockStart->lessThan($day->addDay()) && $blockEnd->greaterThan($day))
            @php $professional = collect($professionals)->firstWhere('id', $block['professionalId']); @endphp
            <div class="calendar-block" data-block-professional="{{ $professional['id'] }}" style="top: {{ $calendarStart->diffInMinutes($visibleStart) / 60 * 64 }}px; height: {{ $visibleStart->diffInMinutes($visibleEnd) / 60 * 64 }}px"><p class="flex items-center gap-1"><x-icon name="lock" size="10" />{{ $professional['name'] }}</p><p>{{ $block['reason'] }}</p></div>
        @endif
    @endforeach
    @foreach($dayItems as $entry)
        @php
            ['item' => $item, 'appointment' => $appointment, 'start' => $start] = $entry;
            $professional = collect($professionals)->firstWhere('id', $item['professionalId']);
            $client = collect($clients)->firstWhere('id', $appointment['clientId']);
            $minutes = ($start->hour * 60 + $start->minute) - $calendarStartMinutes;
        @endphp
        <button type="button" data-open="appointment-{{ $appointment['id'] }}" data-calendar-card data-professional="{{ $professional['id'] }}" data-start="{{ $minutes }}" data-duration="{{ $item['durationSnapshot'] }}" class="calendar-card" style="--professional-color: {{ $professional['color'] }}; top: {{ $minutes / 60 * 64 + 1 }}px; height: {{ max(28, $item['durationSnapshot'] / 60 * 64) - 2 }}px; left: calc({{ $entry['column'] / $columns * 100 }}% + 2px); width: calc({{ 100 / $columns }}% - 4px)" title="{{ $client['name'] }} · {{ $item['serviceNameSnapshot'] }} · {{ $professional['name'] }} · {{ $start->format('H:i') }}">
            <p class="truncate font-semibold text-slate-800">{{ $client['name'] }}</p><p class="truncate text-slate-600">{{ $item['serviceNameSnapshot'] }}</p><p class="truncate font-medium" style="color: {{ $professional['color'] }}">{{ explode(' ', $professional['name'])[0] }}</p><p class="truncate text-slate-500">{{ $start->format('H:i') }} – {{ $start->addMinutes($item['durationSnapshot'])->format('H:i') }}</p>
            <span class="{{ $appointment['status'] === 'completed' ? 'text-green-600' : ($appointment['status'] === 'confirmed' ? 'text-blue-600' : 'text-slate-500') }}">@if($appointment['status'] !== 'scheduled')✓ @endif{{ $statuses[$appointment['status']] }}</span>
        </button>
    @endforeach
</div>
