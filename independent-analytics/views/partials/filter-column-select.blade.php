<select class="filters-column" data-filters-target="column" data-action="filters#columnSelect">

    <option value="">
        <?php esc_html_e('Choose a column', 'independent-analytics'); ?>
    </option>

    @foreach($column_sections as $section_name => $column_section)
        <optgroup label="{{  esc_attr($section_name . (($column_section['plugin_group'])->requires_pro() && iawp_is_free() ? ' (PRO)' : '')) }}">
            @foreach($column_section['columns'] as $column)
                @if(!$column->is_group_plugin_enabled())
                    <option disabled>{{ esc_html($column->name()) }}</option>
                    @continue
                @endif

                <option value="{{ esc_attr($column->id()) }}"
                        data-type="{{ esc_attr($column->type()) }}">
                    {{ esc_html($column->name()) }}
                </option>
            @endforeach
        </optgroup>
    @endforeach
</select>