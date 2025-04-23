@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Modules\Top_Ten_Module $module */ @endphp
@php /** @var bool $is_loaded */ @endphp
@php /** @var ?array $dataset */ @endphp

@extends('overview.modules.layout')

@section('empty')
    <p class="no-data-message"><span class="dashicons dashicons-chart-bar"></span> {{ esc_html__('No data found in this date range.', 'independent-analytics') }}</p>
@endsection

@section('content')
    <div class="iawp-module-table">
        <span class="iawp-module-table-heading">{{ sanitize_text_field($module->primary_column_name()) }}</span>
        <span class="iawp-module-table-heading">{{ sanitize_text_field($module->metric_column_name()) }}</span>
        @if($is_loaded)
            @foreach($dataset as $item)
                <span>
                    <span class="module-row-number">{{ sanitize_text_field($loop->iteration) }}</span>
                    <span>{{ sanitize_text_field($item[0]) }}</span>
                </span>
                <span>{{ sanitize_text_field($item[1]) }}</span>
            @endforeach
        @else
            @for ($i = 0; $i < 20; $i++)
                <span class="skeleton-loader"></span>
            @endfor
        @endif
    </div>
@endsection