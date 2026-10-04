<div class="eo-steps">
    @foreach ($overview->steps() as $index => $step)
        <div class="eo-step {{ $step['state'] }}">
            <div class="eo-step-title">
                @if ($step['state'] === 'done')
                    <x-heroicon-s-check-circle />
                @elseif ($step['state'] === 'current')
                    <x-heroicon-s-arrow-right-circle />
                @else
                    <x-heroicon-o-ellipsis-horizontal-circle />
                @endif
                {{ $index + 1 }}. {{ $step['title'] }}
            </div>
            <div class="eo-step-hint">{{ $step['hint'] }}</div>
        </div>
    @endforeach
</div>
