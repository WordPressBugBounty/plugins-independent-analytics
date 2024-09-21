@php /** @var \IAWP\Chart $chart */ @endphp
@php /** @var \IAWP\Statistics\Intervals\Interval[] $intervals */ @endphp
@php /** @var \IAWP\Statistics\Intervals\Interval $current_interval */ @endphp
@php /** @var array $stimulus_values */ @endphp
@php /** @var array $available_datasets */ @endphp
@php /** @var string $primary_chart_metric_id */ @endphp
@php /** @var ?string $secondary_chart_metric_id */ @endphp

<div class="chart-container">
    <div class="chart-inner"
         data-testid="chart"
         data-controller="chart"
         @foreach($stimulus_values as $key => $value)
             data-chart-{{$key}}-value="{{is_array($value) ? $chart->encode_json($value) : $value}}"
            @endforeach
    >
        <div class="legend-container">
            <div class="legend" style="display: none;"></div>
            @if(!$chart->is_preview())
                <div class="primary-metric-select-container metric-select-container">
                    <select id="primary-metric-select" 
                            data-chart-target="primaryMetricSelect"
                            data-action="chart#changePrimaryMetric"
                    >
                        @foreach($available_datasets as $group)
                            <optgroup label="{{ $group['name'] }}">
                                @foreach($group['items'] as $item)
                                    <option value="{{ $item['id'] }}" {!! selected($primary_chart_metric_id, $item['id'], true) !!} {!! $secondary_chart_metric_id === $item['id'] ? 'disabled' : '' !!}>{{ $item['name'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="secondary-metric-select-container metric-select-container">
                    <select id="secondary-metric-select" 
                            data-chart-target="secondaryMetricSelect"
                            data-action="chart#changeSecondaryMetric"
                    >
                        <option value="no_comparison" {!! selected(is_null($secondary_chart_metric_id)) !!}><?php esc_html_e('No Comparison', 'independent-analytics'); ?></option>
                        @foreach($available_datasets as $group)
                            <optgroup label="{{ $group['name'] }}">
                                @foreach($group['items'] as $item)
                                    <option value="{{ $item['id'] }}" {!! selected($secondary_chart_metric_id, $item['id'], true) !!} {!! $primary_chart_metric_id === $item['id'] ? 'disabled' : '' !!}>{{ $item['name'] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <select class="adaptive-select-width" data-chart-target="adaptiveWidthSelect">
                    <option></option>
                </select>

                <select id="chart-interval-select" class="chart-interval-select"
                        data-controller="chart-interval"
                        data-action="chart-interval#setChartInterval">
                    @foreach($intervals as $interval)
                        <option value="{{ esc_attr($interval->id()) }}"
                                @php selected($interval->equals($current_interval)) @endphp
                        >
                            {{ $interval->label() }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
        <canvas id="independent-analytics-chart" width="800" height="@if($chart->is_preview()) 400 @else 200 @endif"></canvas>
    </div>
</div>