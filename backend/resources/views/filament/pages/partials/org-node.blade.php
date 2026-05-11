@php
    $unit = $node['unit'];
    $head = $node['head'] ?? null;
    $children = $node['children'] ?? [];
    $depth = $depth ?? 0;

    $bandColor = match ($unit->type) {
        'directie' => '#1F5E3A',
        'afdeling' => '#194a2e',
        'dienst'   => '#408a5e',
        'sectie'   => '#97c6a8',
        default    => '#94a3b8',
    };

    $typeLabel = match ($unit->type) {
        'directie' => 'Directie',
        'afdeling' => 'Afdeling',
        'dienst'   => 'Dienst',
        'sectie'   => 'Sectie',
        default    => 'Eenheid',
    };
@endphp

<div class="mas-org-row" style="margin-left: {{ $depth * 24 }}px;">
    <div class="mas-org-band" style="background: {{ $bandColor }};"></div>

    <div class="mas-org-name">
        <span class="mas-org-type" style="background: {{ $bandColor }};">{{ $typeLabel }}</span>
        <div class="mas-org-titles">
            <div class="mas-org-unitname">{{ $unit->name }}</div>
            <div class="mas-org-code">{{ $unit->code }}</div>
        </div>
    </div>

    @if ($head)
        <div class="mas-org-head">
            <div class="mas-org-avatar">
                @if (! empty($head['avatar_url']))
                    <img src="{{ $head['avatar_url'] }}" alt="{{ $head['name'] }}">
                @else
                    <span>{{ $head['initials'] }}</span>
                @endif
            </div>
            <div class="mas-org-headinfo">
                <div class="mas-org-headname">{{ $head['name'] }}</div>
                <div class="mas-org-headtitle">{{ $head['title'] }}</div>
            </div>
        </div>
    @else
        <div class="mas-org-head mas-org-head-empty">
            <span>— vacant —</span>
        </div>
    @endif

    @if ($unit->positions_count > 0)
        <div class="mas-org-stats" title="Bezette posities">
            <strong>{{ $unit->occupied_employees_count ?? 0 }}</strong>/{{ $unit->positions_count }}
        </div>
    @else
        <div class="mas-org-stats mas-org-stats-empty">—</div>
    @endif
</div>

@foreach ($children as $child)
    @include('filament.pages.partials.org-node', ['node' => $child, 'depth' => $depth + 1])
@endforeach
