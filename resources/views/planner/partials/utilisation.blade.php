@php $utilisation = $utilisation ?? ['average' => 0, 'guide' => 0, 'vehicle' => 0, 'bars' => []]; @endphp
<section class="panel utilisation-panel">
    <div class="panel-heading">
        <div><h2>Resource utilisation</h2><p>Assigned resources across today’s visible bookings</p></div>
        <button class="icon-button" type="button" aria-label="Refresh resource utilisation" onclick="window.refreshOverview?.()"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 12h.01M12 12h.01M19 12h.01"/></svg></button>
    </div>
    <div class="utilisation-chart">
        <div class="chart-value">{{ $utilisation['average'] }}<span>%</span></div>
        <div class="chart-label">Average utilisation</div>
        <div class="bars">
            @foreach($utilisation['bars'] as $index => $height)
                <div class="bar-wrap"><div class="bar {{ $index >= count($utilisation['bars']) - 3 ? 'bar-active' : '' }}" style="height: {{ $height }}%" title="{{ $height }}% activity volume"></div></div>
            @endforeach
        </div>
        <div class="chart-legend"><span><i class="legend-swatch sage"></i> Guides <b>{{ $utilisation['guide'] }}%</b></span><span><i class="legend-swatch blue"></i> Vehicles <b>{{ $utilisation['vehicle'] }}%</b></span></div>
    </div>
</section>
