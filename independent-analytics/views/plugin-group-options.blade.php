@php /** @var \IAWP\Plugin_Group[] $plugin_groups */ @endphp
@php /** @var \IAWP\Plugin_Group_Option[] $options */ @endphp
@php /** @var string $option_type */ @endphp
@php /** @var string $option_name */ @endphp
@php /** @var string $option_icon */ @endphp

{{-- Array indicies are used to add section headers. Make sure there are no gaps. --}}
@php $options = array_values($options); @endphp

@php
$plugin_groups = array_filter($plugin_groups, function ($plugin_group) use ($options) {
    foreach ($options as $option) {
        if($option->is_member_of_plugin_group($plugin_group->id())) {
            return true;
        }
    }

    return false;
});
@endphp

@php
    // Only one group with one option? Don't render the button or modal.
    if(count($plugin_groups) === 1) {
        $plugin_group_options = array_filter($options, function ($option) use ($plugin_groups) {
           return $option->is_member_of_plugin_group($plugin_groups[0]->id());
        });

        if(count($plugin_group_options) === 1) {
            return;
        }
    }
@endphp

<div data-controller="plugin-group-options"
     data-plugin-group-options-option-type-value="{{ $option_type }}"
     class="button-modal-container"
>
    {{-- Toggle button --}}
    <button id=""
            class="stats-toggle-button iawp-button"
            data-plugin-group-options-target="modalButton"
            data-action="plugin-group-options#toggleModal"
    >
        <span class="dashicons dashicons-{{ $option_icon }}"></span>
        {{ $option_name }}
    </button>

    {{-- Toggle modal --}}
    <div data-plugin-group-options-target="modal"
         class="stats-toggle"
    >
        <div class="top title-small">
            <?php esc_html_e('Choose Stats to Display', 'independent-analytics'); ?>
            <span data-plugin-group-options-target="spinner" class="dashicons dashicons-update spin hidden"></span>
        </div>
        <div class="inner">
            <div id="stats-toggle-sidebar" class="sidebar">
                <ul>
                    @foreach($plugin_groups as $plugin_group)
                        <li>
                            <a class="link-dark {{ $plugin_group->id() === 'general' ? 'current' : '' }}"
                               data-option-id="{{ $plugin_group->id() }}"
                               data-action="plugin-group-options#requestGroupChange"
                               data-plugin-group-options-target="tab"
                               href="#"
                            >
                                <span>{{ $plugin_group->name() }}</span>
                                @if($plugin_group->requires_pro() && iawp_is_free())
                                    <span class="pro-label"><?php esc_html_e('PRO', 'independent-analytics'); ?></span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="main">
                @foreach($plugin_groups as $plugin_group)
                    <div class="checkbox-container {{ $plugin_group->id() === 'general' ? 'current' : '' }}"
                         data-option-id="{{ $plugin_group->id() }}"
                         data-plugin-group-options-target="checkboxContainer"
                            {{--                             data-metric-category="{{ $plugin_group->id() }}"--}}
                    >
                        <span class="metrics-title">{{ $plugin_group->name() }}</span>

                        @foreach($options as $index => $option)
                            @if(!$option->is_subgroup_plugin_enabled() || !$option->is_member_of_plugin_group($plugin_group->id()))
                                @continue
                            @endif

                            @if(is_string($option->plugin_group_header()) && array_key_exists($index - 1, $options) && ($options[$index -1])->plugin_group_header() !== $option->plugin_group_header())
                                <span class="metrics-subtitle">{{ $option->plugin_group_header() }}</span>
                            @endif

                            <label class="{{ !$option->is_group_plugin_enabled() ? 'disabled' : '' }}">
                                @if(!$option->is_group_plugin_enabled())
                                    <input id="iawp_{{ $option_type }}_{{$option->id()}}"
                                           type="checkbox" 
                                           disabled="disabled">
                                @else
                                    <input id="iawp_{{ $option_type }}_{{$option->id()}}"
                                           type="checkbox"
                                           data-action="plugin-group-options#toggleOption"
                                           data-plugin-group-options-target="checkbox"
                                           name="{{ $option->id() }}"
                                           {{ checked(true, $option->is_visible(), true) }}
                                    />
                                @endif
                                <span>{{ $option->name() }}</span>
                            </label>
                        @endforeach

                        @if($plugin_group->requires_pro() && iawp_is_free())
                            <div class="required-plugin-note">
                                <p>{{ $plugin_group->upgrade_message() }}</p>
                                <p><a href="{{ $plugin_group->upgrade_link() }}"
                                      class="link-purple"
                                      target="_blank"><?php esc_html_e('Learn more', 'independent-analytics'); ?></a>
                                </p>
                            </div>
                        @elseif(!$plugin_group->has_active_group_plugins() && iawp_is_pro())
                            <div class="required-plugin-note">
                                <p>{{ $plugin_group->activate_message() }}</p>
                                <p><a href="{{ $plugin_group->activate_link() }}"
                                      class="link-purple"
                                      target="_blank"><?php esc_html_e('Learn more', 'independent-analytics'); ?></a>
                                </p>
                            </div>
                        @elseif(!$plugin_group->has_tracked_data())
                            <div class="required-plugin-note">
                                <p>{{$plugin_group->no_tracked_data_message()}}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>