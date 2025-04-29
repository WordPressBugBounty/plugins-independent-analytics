@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Modules\Busiest_Day_Of_Week_Module $module */ @endphp
@php /** @var bool $is_loaded */ @endphp
@php /** @var ?array $dataset */ @endphp

@extends('overview.modules.layout')

@section('content')
    @if(is_array($dataset))
        <div data-controller="chart"
             class="module-chart"
             data-chart-labels-value="{{ json_encode($module->get_labels($dataset)) }}"
             data-chart-data-value="{{ json_encode([
                'sessions' => $module->get_sessions($dataset),
             ]) }}"
             data-chart-locale-value="{{ esc_attr(get_bloginfo('language')) }}"
             data-chart-currency-value="{{ esc_attr(iawp()->get_currency_code()) }}"
             data-chart-is-preview-value="1"
             data-chart-primary-chart-metric-id-value="sessions"
             data-chart-primary-chart-metric-name-value="{{ __('Sessions', 'independent-analytics') }}"
             data-chart-secondary-has-multiple-datasets-value="0"
        >
            <canvas data-chart-target="canvas" height="300"></canvas>
        </div>
    @else
        <div class="loading-message">
            <img src="<?php echo esc_url(iawp_url_to('img/loading.svg')) ?>" />
            <p>{{ esc_html__('Loading data...', 'independent-analytics') }}</p>
        </div>
    @endif
@endsection