@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Modules\Module $module */ @endphp

<div class="iawp-module module-editor {{ $module !== null && $module->is_full_width() ? 'full-width' : '' }}"
     data-controller="module-editor"
     data-module-editor-module-id-value="{{ $module->id() }}"
     data-module-editor-reports-value="{{ esc_attr(json_encode($module->get_report_details())) }}">
    <header class="module-header">
        <div class="module-icon">
            @include('icons.overview.' . $module->module_type())
        </div>
        <div class="module-title-container">
            <h2>{{ $module->module_name() }}</h2>
        </div>
        <button class="iawp-button module-editing-buttons change-module-type" data-action="module-editor#changeModuleType">{{ __('Change Module Type', 'independent-analytics') }}</button>
        <button class="iawp-button module-editing-buttons cancel-module-edit" data-module-editor-target="cancelButton" data-action="module-editor#cancel">{{ __('Cancel', 'independent-analytics') }}</button>
    </header>

    <div class="module-contents">
        <div>
            <form class="iawp-module-editor-form" data-action="module-editor#save">
                <input type="hidden" name="module_type" value="{{ $module->module_type() }}">
                <div>
                    <label>{{ esc_html__('Name', 'independent-analytics') }}</label>
                    <input type="text"
                        name="name"
                        value="{{ $module->is_saved() ? $module->name() : $module->module_name() }}"
                        autofocus
                        required
                        data-1p-ignore
                    >
                </div>

                {!! $module->get_form_fields_html() !!}

                <footer>
                    <button type="submit"
                            class="module-save-button iawp-button purple"
                            data-module-editor-target="saveButton"
                            data-loading-text="{{ __('Saving...') }}"
                    >
                    {{ $module->is_saved() ? __('Save', 'independent-analytics') : __('Add Module', 'independent-analytics') }}
                    </button>
                </footer>
            </form>
        </div>
    </div>
</div>