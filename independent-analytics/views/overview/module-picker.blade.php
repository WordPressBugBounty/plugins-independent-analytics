@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Overview $overview */ @endphp
@php /** @var \IAWP\Overview\Modules\Module[] $saved_modules */ @endphp
@php /** @var \IAWP\Overview\Modules\Module[] $template_modules */ @endphp

<div data-controller="module-picker"
     data-action="add-module:addModule@window->module-picker#scrollToPicker"
     class="iawp-module module-picker show-intro"
>
    <div class="module-intro">
        <button class="add-module-button" data-action="module-picker#showList">
            <span class="button-inner iawp-button">{{ esc_html__('Add Module', 'independent-analytics') }}</span>
        </button>
    </div>

    <div class="module-picker-inner">
        <div class="module-picker-header">
            <span>{{ esc_html__('Choose a module', 'independent-analytics') }}</span>
            <button class="iawp-button" data-action="module-picker#cancel">{{ esc_html__('Cancel', 'independent-analytics') }}</button>
        </div>

        <ul class="module-picker-list">
            @foreach($template_modules as $module)
                <li>
                    <button data-action="module-picker#showModule"
                            data-module-id="{{ $module->module_type() }}">
                        <span class="module-icon">@include('icons.overview.' . $module->module_type())</span>
                        <span class="module-name">{{ $module->module_name() }}</span>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</div>