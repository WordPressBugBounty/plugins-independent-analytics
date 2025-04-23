<div class="iawp-stat visible {{ $id }}"
        data-id="{{ $id }}" data-quick-stats-target="quickStat">
    <div class="metric">
        <span class="metric-name">{{ $name }}</span>
        @if(!is_null($icon))
            <span class="plugin-label">{!! iawp_icon($icon) !!}</span>
        @endif
    </div>
    <div class="values">
        <span class="count">
            <span class="skeleton-loader"></span>
        </span>
    </div>
    <span class="growth">
        <span class="percentage">
            <span class="skeleton-loader"></span>
        </span> 
    </span>
</div>