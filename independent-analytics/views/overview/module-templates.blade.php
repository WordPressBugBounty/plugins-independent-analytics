@php /** @var \IAWP\Env $env */ @endphp
@php /** @var \IAWP\Overview\Overview $overview */ @endphp
@php /** @var \IAWP\Overview\Modules\Module[] $saved_modules */ @endphp
@php /** @var \IAWP\Overview\Modules\Module[] $template_modules */ @endphp

{{-- Template to reshow the module picker --}}
<template id="module-picker-template">
    @include('overview.module-picker', [ 'show_list' => true ])
</template>

{{-- Template for every supported module type --}}
@foreach($template_modules as $module)
    <template id="{{ $module->module_type() }}-module-template">
        @include('overview.module-editor', [
            'module' => $module
        ])
    </template>
@endforeach