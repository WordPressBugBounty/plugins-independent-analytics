@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Modules\Recent_Conversions_Module $module */ @endphp
@php /** @var ?array $dataset */ @endphp

@extends('overview.modules.layout')

@section('empty')
    <p class="no-data-message"><span class="dashicons dashicons-chart-bar"></span> {{ esc_html__('No data found in this date range.', 'independent-analytics') }}</p>
@endsection

@section('content')
    @if(is_array($dataset))
        <div>
            @php $dataset = $module->add_icons_to_dataset($dataset) @endphp
            @for ($i = 0; $i < count($dataset); $i++)
                @if ($i % 10 == 0)
                    <div class="module-page module-page-{{ $i/10 + 1 }} {{ $i == 0 ? 'current' : ''}} conversions-grid">
                @endif
                <div>
                    <span class="icon-container">
                        <span class="icon">{{ sanitize_text_field($dataset[$i]['viewed_at']) }}</span>
                        <span class="icon-label">{{ sanitize_text_field($dataset[$i]['viewed_at_the_long_way']) }}</span>
                    </span>
                </div>
                <div>
                    <span class="icon-container">
                        <span class="icon">{!! wp_kses($dataset[$i]['flag'], 'post') !!}</span>
                        <span class="icon-label">{{ sanitize_text_field($dataset[$i]['country']) }}</span>
                    </span>
                </div>
                <div>
                    <span class="icon-container">
                        <span class="icon">{!! wp_kses($dataset[$i]['device_type_icon'], 'post') !!}</span>
                        <span class="icon-label">{{ sanitize_text_field($dataset[$i]['device_type']) }}</span>
                    </span>
                </div>
                <div>
                    <span class="icon-container">
                        <span class="icon">{!! wp_kses($dataset[$i]['browser_icon'], 'post') !!}</span>
                        <span class="icon-label">{{ sanitize_text_field($dataset[$i]['browser']) }}</span>
                    </span>
                </div>
                <div>
                    <span class="conversion-type {{ esc_attr($dataset[$i]['conversion_type']) }}">{{ sanitize_text_field($dataset[$i]['conversion_label']) }}</span>
                </div>
                        <div class="page-title">
                            {{ $dataset[$i]['name'] }}
                        </div>
                @if (($i + 1) % 10 == 0 || $i == count($dataset) - 1)
                    </div>
                @endif
            @endfor
            @if (count($dataset) > 10)
                <div class="module-pagination">
                    <button class="pagination-button left" disabled><span class="dashicons dashicons-arrow-left-alt2"></span></button>
                    <span class="page-count">
                        <span class="current-page">1</span>
                        <span>/</span>
                        <span class="full-width-count">{{ ceil(count($dataset) / 20 ) }}</span>
                        <span class="regular-count">{{ ceil(count($dataset) / 10 ) }}</span>
                    </span>
                    <button class="pagination-button right"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
                </div>
            @endif
        </div>
    @else
        <div class="loading-message">
            <img src="<?php echo esc_url(iawp_url_to('img/loading.svg')) ?>" />
            <p>{{ esc_html__('Loading data...', 'independent-analytics') }}</p>
        </div>
    @endif
@endsection