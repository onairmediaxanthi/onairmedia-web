<li class="flex items-center gap-4 px-4 py-3 {{ $big ? 'sm:py-4' : '' }}">
    <span class="w-8 shrink-0 text-center font-extrabold freq {{ $big ? 'text-2xl' : 'text-lg text-slate' }}" @if($big) style="color: {{ $station->color ?: '#c80c92' }}" @endif>{{ $c->position }}</span>
    <span class="min-w-0 flex-1">
        <span class="block truncate font-semibold {{ $big ? 'text-lg' : '' }}">{{ $c->song->title }}</span>
        @if ($c->song->artist)<span class="block truncate text-sm text-slate">{{ $c->song->artist }}</span>@endif
    </span>
    <span class="hidden shrink-0 text-right text-xs text-slate sm:block">
        {{ $c->periods_on }} {{ ($unit ?? 'week') === 'month' ? ($c->periods_on === 1 ? 'μήνας' : 'μήνες') : ($c->periods_on === 1 ? 'εβδομάδα' : 'εβδομάδες') }}@if($c->peak < $c->position)<br>κορυφή #{{ $c->peak }}@endif
    </span>
    <span class="w-12 shrink-0 rounded-full px-2 py-0.5 text-center text-xs font-bold {{ $m[1] }}" title="{{ $c->prev_position ? 'Προηγούμενη θέση: #'.$c->prev_position : 'Νέα είσοδος' }}">{{ $m[0] }}</span>
</li>
