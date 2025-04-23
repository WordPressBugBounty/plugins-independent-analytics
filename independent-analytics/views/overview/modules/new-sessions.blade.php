@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Modules\Module $module */ @endphp
@php /** @var bool $is_loaded */ @endphp
@php /** @var ?array $dataset */ @endphp

@extends('overview.modules.layout')

@section('empty')
    <p class="no-data-message"><span class="dashicons dashicons-chart-bar"></span> {{ esc_html__('No data found in this date range.', 'independent-analytics') }}</p>
@endsection

@section('content')
    <div class="new-sessions-module {{ $is_loaded ? "is-loaded" : "is-loading" }}">
        @if($is_loaded)
            <div data-controller="pie-chart"
                 data-pie-chart-data-value="{{ json_encode($dataset) }}"
                 data-pie-chart-locale-value="{{ esc_attr(get_bloginfo('language')) }}"
            >
                <canvas data-pie-chart-target="canvas"></canvas>
            </div>
        @else
            <div class="loading-message">
                <img src="<?php echo esc_url(iawp_url_to('img/loading.svg')) ?>" />
                <p>{{ esc_html__('Loading data...', 'independent-analytics') }}</p>
            </div>
        @endif
    </div>
@endsection