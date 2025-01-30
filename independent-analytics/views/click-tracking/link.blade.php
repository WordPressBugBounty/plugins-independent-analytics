<div class="trackable-link" data-id="{{ esc_attr($link['id']) }}">
    <div class="input-container">
        <div class="inner-container name">    
            <input class="link-name" type="text" value="{{ esc_attr($link['name']) }}" />
        </div>
        <div class="inner-container type">
            <select class="link-type" value="{{ esc_attr($link['type']) }}">
                @foreach ($types as $type => $title)
                    <option value="{{ esc_attr($type) }}" {{ selected($type, $link['type'], true )}}>{{ esc_html($title) }}</option>    
                @endforeach
            </select>
        </div>
        <div class="inner-container value">
            @foreach ($types as $type => $title)
                <span class="value-container {{ esc_attr($type) }} {{ $type == $link['type'] ? 'visible' : '' }}">
                    @if ($type == 'extension')
                        <select class="link-value">
                            @foreach ($extensions as $extension)
                                <?php $selected = $link['type'] !== 'extension' ? 'pdf' : $link['value']; ?>
                                <option value="{{ esc_attr($extension) }}" {{ selected($extension, $selected, true)}}>{{ esc_html($extension) }}</option>
                            @endforeach
                        </select>
                    @elseif ($type == 'protocol')
                        <select class="link-value">
                            @foreach ($protocols as $protocol)
                                <option value="{{ esc_attr($protocol) }}" {{ selected($protocol, $link['value'], true)}}>{{ esc_html($protocol) }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'external')
                        <input class="link-value external" type="text" disabled/>
                    @else
                        @if ($type == 'class')
                            <span class="value-prefix">.</span>
                        @endif
                        @if ($type == 'subdirectory')
                            <span class="value-prefix">/</span>
                        @endif
                        <input class="link-value {{ esc_attr($type) }}" type="text" value="{{ esc_attr($type == $link['type'] ? $link['value'] : '') }}" />
                        @if ($type == 'subdirectory')
                            <span class="value-suffix">/</span>
                        @endif
                    @endif
                </span>
            @endforeach
        </div>  
    </div>
    <div class="value-text-container">
        <span class="name">
            @if ($link['is_active'])
                <span class="dashicons dashicons-yes-alt"></span>
            @else
                <span class="dashicons dashicons-dismiss"></span>
            @endif
            {{ esc_html($link['name']) }}
        </span>
        <span class="type">{{ esc_html($types[$link['type']]) }}</span>
        <span class="value">
            @if ($link['type'] == 'class')
                {{ '.' . esc_html($link['value']) }}
                <button class="copy-class" data-controller="clipboard" data-action="clipboard#copy"
                    data-clipboard-text-value="{{ esc_attr($link['value']) }}">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            @elseif ($link['type'] == 'extension')
                {{ '.' . esc_html($link['value']) }}
            @elseif ($link['type'] == 'domain')
                {{ esc_html($link['value']) }}
            @elseif ($link['type'] == 'subdirectory')
                {{ '/' . esc_html($link['value']) . '/' }}
            @elseif ($link['type'] == 'protocol')
                {{ esc_html($link['value']) . ':' }}
            @else
                {{ esc_html($link['value']) }}
            @endif 
        </span>
    </div>
    @if ($link['is_active'] !== false)
        <div class="action-buttons">
            <div class="edit-container">
                <button class="edit-button">{{ esc_html__('Edit', 'independent-analytics') }}</button>
            </div>
            <div class="save-cancel-container">
                <button class="save-button">{{ esc_html__('Save', 'independent-analytics') }}</button>
                <button class="cancel-button">{{ esc_html__('Cancel', 'independent-analytics') }}</button>
            </div>
        </div>
    @endif
    <button class="archive-button">
        @if($link['is_active'])
            {{ esc_html__('Archive', 'independent-analytics' )}}
        @else
            {{ esc_html__('Resume Tracking', 'independent-analytics' )}}
        @endif
    </button>
    @if ($link['is_active'] === false) 
        <button class="delete-link-button">{{ esc_html__('Delete', 'independent-analytics' )}}</button>
    @endif
</div>