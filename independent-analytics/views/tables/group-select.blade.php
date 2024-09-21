@php /** @var \IAWP\Tables\Groups\Groups $groups */ @endphp
@php /** @var \IAWP\Tables\Groups\Group $current_group */ @endphp

@if($groups->has_grouping_options())
    <div class="group-select-container">
        <select id="group-select"
                class="group-select"
                data-controller="group"
                data-action="group#changeGroup"
        >
            @foreach($groups->groups() as $group)
                <option id="{{ esc_attr($group->id()) }}"
                        value="{{ esc_attr($group->id()) }}"
                        data-testid="group-by-{{ esc_attr($group->id()) }}"
                        {{ selected($group->id(), $current_group->id(), true) }}
                >
                    {{ esc_html($group->singular()) }}
                </option>
            @endforeach
        </select>
        <label><span class="dashicons dashicons-open-folder"></span></label>
    </div>
@endif