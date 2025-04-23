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
    <div class="iawp-stats total-of-{{ esc_attr($total_stats) }}">
        @foreach($statistics as $statistic)
            @if($is_dashboard_widget && !$statistic->is_visible_in_dashboard_widget())
                @continue
            @endif

            @if(!$statistic->is_group_plugin_enabled())
                @continue
            @endif
            {!!
                iawp_blade()->run('quick-stat', [
                    'id'     => $statistic->id(),
                    'name'   => $statistic->name(),
                    'formatted_value' => $statistic->formatted_value(),
                    'formatted_unfiltered_value' => $statistic->formatted_unfiltered_value(),
                    'growth' => $statistic->growth(),
                    'formatted_growth' => $statistic->formatted_growth(),
                    'growth_html_class' => $statistic->growth_html_class(),
                    'icon'   => $statistic->icon(),
                    'is_visible' => $statistic->is_visible()
                ])
            !!}
        @endforeach
    </div>
</div>