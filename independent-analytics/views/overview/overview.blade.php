@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Overview $overview */ @endphp
@php /** @var string $last_refreshed_at */ @endphp
@php /** @var \IAWP\Overview\Modules\Module[] $saved_modules */ @endphp
@php /** @var \IAWP\Overview\Modules\Module[] $template_modules */ @endphp

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<div id="report-header-container" class="report-header-container">
    <div id="report-title-bar" class="report-title-bar overview-report">
        <div class="primary-report-title-container">
            <h1 class="report-title">{{ __('Overview', 'independent-analytics') }}</h1>
            <div class="last-updated-container">
                <span id="iawp-modules-refreshed-at">{{ sanitize_text_field($last_refreshed_at) }}</span>
                <button class="refresh-overview-button"
                        data-controller="refresh-overview"
                        data-action="refresh-overview#refresh"
                        data-refresh-overview-loading-text-value="{{ __('Refreshing...', 'independent-analytics') }}"
                >{{ __('Refresh', 'independent-analytics') }}</button>
            </div>
        </div>
        <div class="buttons">
            <div>
                <button id="favorite-report-button"
                        data-controller="set-favorite-report"
                        data-set-favorite-report-type-value="overview"
                        data-action="set-favorite-report#setFavoriteReport"
                        class="iawp-button favorite {{$env->is_favorite('overview') ? 'active' : '' }}"
                >
                    <span class="dashicons dashicons-star-filled"></span>
                    {{ __('Make default', 'independent-analytics') }}
                </button>
            </div>
        </div>
    </div>
    <div id="toolbar" class="toolbar">
        <div class="overview-toolbar-buttons">
            <button data-controller="add-module" data-action="add-module#addModule" class="iawp-button add-module-toolbar-button"><span class="dashicons dashicons-plus"></span> <?php esc_html_e('Add Module', 'independent-analytics'); ?></button>
            <button data-controller="reorder-modules" data-action="reorder-modules#toggleReordering" class="iawp-button reorder-modules-button"><span class="dashicons dashicons-sort"></span> <?php esc_html_e('Reorder Modules', 'independent-analytics'); ?></button>
        </div>
        <div class="download-options-parent" data-controller="modal">
            <div class="modal-parent downloads">
                <button id="download-options" data-modal-target="modalButton" data-action="click->modal#toggleModal" class="download-options">
                    <?php esc_html_e('Download Report', 'independent-analytics'); ?>
                </button>
                <div class="iawp-modal small downloads" data-modal-target="modal">
                    <div class="modal-inner">
                        <div class="title-small">
                            <?php esc_html_e('Download report', 'independent-analytics'); ?>
                            <span data-report-target="spinner" class="dashicons dashicons-update spin hidden"></span>
                        </div>
                        <button data-controller="export-overview"
                                data-action="export-overview#export"
                                class="pdf-export-button iawp-button"
                            >{{ __('Download PDF', 'independent-analytics') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div data-controller="module-list">
    <div id="module-list" class="module-list" data-module-list-target="list">
        @foreach($saved_modules as $module)
            {!! $module->get_module_html() !!}
        @endforeach
        @include('overview.module-picker')
    </div>
    @include('overview.module-templates')
</div>
