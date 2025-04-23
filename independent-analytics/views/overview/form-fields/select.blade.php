@php /** @var \IAWP\Overview\Form_Field $form_field */ @endphp
@php /** @var string $selected_value */ @endphp

@php
    $selected_value = $selected_value ?? null;
    $template_values = $form_field->template_values();
    $has_groups = !array_key_exists(0, $template_values);
@endphp

@if($has_groups)
    <div>
        <label>{{ sanitize_text_field($form_field->name()) }}</label>
        <select name="{{ esc_attr($form_field->id()) }}" id="{{ esc_attr($form_field->id()) }}">
            @foreach($template_values as $group_name => $values)
                @php $first_group = $loop->first @endphp
                <optgroup label="{{ esc_attr($group_name) }}">
                    @foreach($values as $value)
                        <option value="{{ esc_attr($value->id()) }}"
                            @if($selected_value !== null)
                                {{ $value->id() == $selected_value ? 'selected' : '' }}
                            @else
                                {{ $first_group && $loop->first ? 'selected' : '' }}
                            @endif
                        >{{ sanitize_text_field($value->name()) }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
@else
    <div>
        <label>{{ sanitize_text_field($form_field->name()) }}</label>
        <select name="{{ esc_attr($form_field->id()) }}" id="{{ esc_attr($form_field->id()) }}">
            @foreach($template_values as $value)
                <option value="{{ esc_attr($value->id()) }}"
                @if($selected_value !== null)
                    {{ $value->id() == $selected_value ? 'selected' : '' }}
                @else
                    {{ $loop->first ? 'selected' : '' }}
                @endif
                >{{ sanitize_text_field($value->name()) }}</option>
            @endforeach
        </select>
    </div>
@endif
