@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Modules\Module $module */ @endphp
@php /** @var bool $is_loaded */ @endphp
@php /** @var ?array $dataset */ @endphp

@extends('overview.modules.layout')

@section('content')
    @if($is_loaded)
        <div data-controller="chart"
             data-chart-labels-value="{{ json_encode($dataset['labels']) }}"
             data-chart-data-value="{{ json_encode([
                 $dataset['primary_dataset_id'] => $dataset['primary_dataset'],
                 $dataset['secondary_dataset_id'] => $dataset['secondary_dataset'],
             ]) }}"
             data-chart-locale-value="{{ esc_attr(get_bloginfo('language')) }}"
             data-chart-currency-value="{{ esc_attr(iawp()->get_currency_code()) }}"
             data-chart-is-preview-value="1"
             data-chart-primary-chart-metric-id-value="{{ esc_attr($dataset['primary_dataset_id']) }}"
             data-chart-primary-chart-metric-name-value="{{ esc_attr($dataset['primary_dataset_name']) }}"
             data-chart-secondary-chart-metric-id-value="{{ esc_attr($dataset['secondary_dataset_id']) }}"
             data-chart-secondary-chart-metric-name-value="{{ esc_attr($dataset['secondary_dataset_name']) }}"
             data-chart-secondary-has-multiple-datasets-value="{{ is_array($dataset['secondary_dataset']) ? '1' : '0' }}"
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