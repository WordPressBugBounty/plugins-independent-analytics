@php /** @var \IAWP\Overview\Form_Field $form_field */ @endphp
@php /** @var string[] $selected_value */ @endphp

<p>{{ sanitize_text_field($form_field->name()) }}</p>

<div id="{{ esc_attr($form_field->id()) }}">
    @foreach($form_field->supported_values() as $value)
        <label>
            <input type="radio"
                   name="{{ esc_attr($form_field->id()) }}"
                   value="{{ esc_attr($value->id()) }}"
            @if($selected_value !== null)
                {{ $value->id() == $selected_value ? 'checked' : '' }}
                    @else
                {{ $loop->first ? 'checked' : '' }}
                    @endif
            />
            <span>{{ sanitize_text_field($value->name()) }}</span>
        </label>
    @endforeach
</div>
