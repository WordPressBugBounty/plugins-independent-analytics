@php /** @var \IAWP\Overview\Form_Field $form_field */ @endphp
@php /** @var string[] $selected_value */ @endphp

@php
    $selected_value = $selected_value ?? null;
    $template_values = $form_field->template_values();
    $has_groups = !array_key_exists(0, $template_values);
@endphp

<span>{{ sanitize_text_field($form_field->name()) }}</span>

@if($has_groups)
    <div id="{{ esc_attr($form_field->id()) }}">
        <div class="checkbox-group-container" data-controller="checkbox-group">
            <div class="tab-container">
                @foreach($template_values as $group_name => $values)
                    <button type="button"
                            class="checkbox-group-tab {{ $loop->first ? "selected" : "" }}"
                            data-group-name="{{ esc_attr($group_name) }}"
                            data-checkbox-group-target="groupTab"
                            data-action="checkbox-group#changeTab"
                    >{{ sanitize_text_field($group_name) }}</button>
                @endforeach
            </div>
            @foreach($template_values as $group_name => $values)
                @php $first_group = $loop->first @endphp
                <div data-checkbox-group-target="group"
                     data-group-name="{{ esc_attr($group_name) }}"
                     class="checkbox-group {{ $loop->first ? "selected" : "" }}"
                >
                    @foreach($values as $value)
                        <label>
                            <input type="checkbox"
                                   name="{{ esc_attr($form_field->id()) }}"
                                   value="{{ esc_attr($value->id()) }}"
                                    @if(is_array($selected_value))
                                        {{ in_array($value->id(), $selected_value) ? 'checked' : '' }}
                                    @else
                                        {{ $first_group && $loop->first ? 'checked' : '' }}
                                    @endif
                            />
                            {{ sanitize_text_field($value->name()) }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
@else
    <div id="{{ esc_attr($form_field->id()) }}">
        <div class="checkbox-group selected" data-controller="checkbox-group">
            @foreach($template_values as $value)
                <label>
                    <input type="checkbox"
                           name="{{ esc_attr($form_field->id()) }}"
                           value="{{ esc_attr($value->id()) }}"
                    @if(is_array($selected_value))
                        {{ in_array($value->id(), $selected_value) ? 'checked' : '' }}
                            @else
                        {{ $loop->first ? 'checked' : '' }}
                            @endif
                    />
                    {{ sanitize_text_field($value->name()) }}
                </label>
            @endforeach
        </div>
    </div>
@endif
