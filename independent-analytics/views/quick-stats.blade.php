@php /** @var \IAWP\Plugin_Group[] $plugin_groups */ @endphp
@php /** @var \IAWP\Statistics\Statistic[] $statistics */ @endphp
@php /** @var bool $is_dashboard_widget */ @endphp

<div id="quick-stats" data-controller="quick-stats" class="{{ esc_attr($quick_stats_html_class) }}">
    @if(!$is_dashboard_widget)
        {!!
            iawp_blade()->run('plugin-group-options', [
                'option_type'   => 'quick_stats',
                'option_name'   => __('Toggle Stats', 'independent-analytics'),
                'option_icon'   => 'visibility',
                'plugin_groups' => $plugin_groups,
                'options'       => $statistics,
            ])
        !!}
    @endif

    {{-- Quick stats --}}
    <div class="iawp-stats">
        @foreach($statistics as $statistic)
            @if($is_dashboard_widget && !$statistic->is_visible_in_dashboard_widget())
                @continue
            @endif

            @if(!$statistic->is_group_plugin_enabled())
                @continue
            @endif

            <div class="iawp-stat {{ $statistic->id() }} {{ $statistic->is_visible() ? 'visible' : ''}}"
                 data-id="{{ $statistic->id() }}" data-quick-stats-target="quickStat">
                <div class="metric">
                    <span class="metric-name">{{ $statistic->name() }}</span>
                    @if(!is_null($statistic->icon()))
                        <span class="plugin-label">{!! iawp_icon($statistic->icon()) !!}</span>
                    @endif
                </div>
                <div class="values">
                    <span class="count"
                          test-value="{{ esc_attr(strip_tags($statistic->formatted_value())) }}">
                        {!! wp_kses($statistic->formatted_value(), ['span' => []]) !!}
                        @if($statistic->formatted_unfiltered_value())
                            <span class="unfiltered"> / {!! wp_kses($statistic->formatted_unfiltered_value(), ['span' => []]) !!}</span>
                        @endif
                    </span>
                </div>
                <span class="growth">
                    <span class="percentage {{ esc_attr($statistic->growth_html_class()) }}"
                          test-value="{{ esc_attr($statistic->growth()) }}">
                        <span class="dashicons dashicons-arrow-up-alt growth-arrow"></span>
                            {{ $statistic->formatted_growth() }}
                        </span>
                    <span class="period-label">{{ __('vs. previous period', 'independent-analytics') }}</span>
                </span>
            </div>
        @endforeach
    </div>
</div>