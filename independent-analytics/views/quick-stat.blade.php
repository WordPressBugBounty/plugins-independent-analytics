<div class="iawp-stat {{ $id }} {{ $is_visible ? 'visible' : ''}}"
        data-id="{{ $id }}" data-quick-stats-target="quickStat">
    <div class="metric">
        <span class="metric-name">{{ $name }}</span>
        @if(!is_null($icon))
            <span class="plugin-label">{!! iawp_icon($icon) !!}</span>
        @endif
    </div>
    <div class="values">
        <span class="count"
                test-value="{{ esc_attr(strip_tags($formatted_value)) }}">
            {!! wp_kses($formatted_value, ['span' => []]) !!}
            @if($formatted_unfiltered_value)
                <span class="unfiltered"> / {!! wp_kses($formatted_unfiltered_value, ['span' => []]) !!}</span>
            @endif
        </span>
    </div>
    <span class="growth">
        <span class="percentage {{ esc_attr($growth_html_class) }}"
                test-value="{{ esc_attr($growth) }}">
            <span class="dashicons dashicons-arrow-up-alt growth-arrow"></span>
                {{ $formatted_growth }}
            </span>
        <span class="period-label">{{ __('vs. previous period', 'independent-analytics') }}</span>
    </span>
</div>