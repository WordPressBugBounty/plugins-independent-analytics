@php /** @var \IAWP\Env $env */ @endphp
@php /** @var string $report_name */ @endphp
@php /** @var string $report_type */ @endphp
@php /** @var bool $can_edit_settings */ @endphp
@php /** @var bool $supports_saved_reports */ @endphp
@php /** @var bool $external */ @endphp
@php /** @var bool $upgrade */ @endphp
@php /** @var \IAWP\Report[] $reports */ @endphp

<div class="menu-section {{$report_type}} {{$env->is_currently_viewed($report_type) ? 'current' : ''}} {{$reports == null ? 'no-sub-items' : ''}} {{$upgrade ? 'upgrade' : ''}} {{$external ? 'external' : ''}}">
    <span class="collapsed-icon" data-testid="collapsed-icon-<?php echo esc_attr($report_type); ?>">
        <?php
        echo iawp_blade()->run('icons.' . $report_type); ?>
    </span>
    <div class="report-inner">
        <h3 class="report-name {{ (!$upgrade && $env->is_favorite($report_type)) ? 'favorite' : '' }}"
            data-report-type="{{$report_type}}">
            <span class="icon-container">
                <span class="report-icon">
                    <?php
                    echo iawp_blade()->run('icons.' . $report_type); ?>
                </span>
            </span>
            <a href="<?php echo esc_attr($url) ?>"
               data-testid="menu-link-<?php echo esc_attr($report_type); ?>"
            >
                {{$report_name}}
            </a>
            @if($upgrade)
                <span class="pro-label">Pro</span>
            @endif
            @if($supports_saved_reports && $can_edit_settings)
                <button class="add-new-report" data-controller="create-report"
                        data-action="create-report#create"
                        data-create-report-type-value="<?php echo esc_attr($report_type); ?>"
                        data-testid="add-new-report-<?php echo esc_attr($report_type); ?>"><span
                            class="dashicons dashicons-plus-alt2"></span></button>
            @endif
        </h3>
        @if($reports != null)
            <ol data-controller="{{ $can_edit_settings ? "sortable-reports" : "" }}"
                data-sortable-reports-type-value="<?php echo esc_attr($report_type); ?>">
                @foreach($reports as $report)
                    <li data-report-id="{{$report->id()}}"
                        class="{{$report->is_current() ? 'current' : ''}} {{$report->is_favorite() ? 'favorite' : '' }}">
                        <a href="{{$report->url()}}"
                           data-name-for-report-id="{{$report->id()}}"
                           data-testid="menu-link-<?php echo esc_attr(sanitize_title($report->name())); ?>">{{$report->name()}}</a>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
    <a class="overlay-link" href="<?php echo esc_url($url); ?>" <?php
                                                                echo $external ? 'target="_blank"' : '' ?>></a>
    @if($collapsed_label)
        <span class="collapsed-label">
            <a href="<?php echo esc_url($url); ?>" <?php
                                                       echo $external ? 'target="_blank"' : ''; ?>>
                <?php
                    echo $collapsed_label; echo $external ? '<span class="dashicons dashicons-external"></span>' : ''; ?>
            </a>
        </span>
    @endif
</div>