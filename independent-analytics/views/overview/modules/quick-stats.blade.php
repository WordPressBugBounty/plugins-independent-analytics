@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Modules\Quick_Stats_Module $module */ @endphp
@php /** @var bool $is_loaded */ @endphp
@php /** @var ?array $dataset */ @endphp

@extends('overview.modules.layout')

@section('content')
    @if($is_loaded)
        <div class="iawp-stats total-of-{{ count($dataset) }}">
            @foreach($dataset as $quick_stat)
                {!!
                    iawp_blade()->run('quick-stat', [
                        'id'     => $quick_stat['id'],
                        'name'   => $quick_stat['name'],
                        'formatted_value' => $quick_stat['formatted_value'],
                        'formatted_unfiltered_value' => $quick_stat['formatted_unfiltered_value'],
                        'growth' => $quick_stat['growth'],
                        'formatted_growth' => $quick_stat['formatted_growth'],
                        'growth_html_class' => $quick_stat['growth_html_class'],
                        'icon'   => $quick_stat['icon'],
                        'is_visible' => true
                    ])
                !!}
            @endforeach
        </div>
    @else
        <div class="iawp-stats total-of-{{ count($module->selected_stats()) }}">
            @foreach($module->selected_stats() as $quick_stat)
                {!!
                    iawp_blade()->run('quick-stat-loading', [
                        'id'     => $quick_stat['id'],
                        'name'   => $quick_stat['name'],
                        'icon'   => $quick_stat['icon']
                    ])
                !!}
            @endforeach
        </div>
    @endif
@endsection