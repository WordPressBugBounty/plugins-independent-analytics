@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Modules\Module $module */ @endphp
@php /** @var bool $is_loaded */ @endphp
@php /** @var bool $is_empty */ @endphp
@php /** @var ?array $dataset */ @endphp

<div class="iawp-module {{ $module->is_full_width() ? 'full-width' : '' }}"
     data-controller="module"
     data-module-module-id-value="{{ esc_attr($module->id()) }}"
     data-module-has-dataset-value="{{ $module->has_dataset() ? 'true' : 'false' }}"
>
    <header class="module-header">
        <div class="module-icon">
            @include('icons.overview.' . $module->module_type())
        </div>
        <div class="module-title-container">
            <h2>{{ sanitize_text_field($module->name()) }}</h2>
            <p>{{ sanitize_text_field($module->subtitle()) }}</p>
        </div>
        <div class="module-action-links">
            <button data-action="module#edit" class="edit-module-button"><span class="dashicons dashicons-admin-generic"></span></button>
            <button data-action="module#toggleWidth" class="toggle-width-button"><span class="dashicons dashicons-columns"></span></button>
            <button data-action="module#delete" class="delete-module-button"><span class="dashicons dashicons-trash"></span></button>
        </div>
    </header>
    <div class="module-contents">
        <div class="{{ esc_attr($module->module_type()) }} {{ $is_loaded ? "is-loaded" : "is-loading" }} {{ $is_empty ? "is-empty" : "" }}">
            @if($is_empty)
                @hasSection('empty')
                    @yield('empty')
                @else
                    {{-- Show the content if they didn't provide an empty view --}}
                    @yield('content')
                @endif
            @else
                @yield('content')
            @endif
        </div>
    </div>
</div>
