<div id="click-tracking-menu" class="click-tracking-menu">
    <div class="settings-container">
        <div class="settings-container-header">
            <h1>{{ esc_html__('Click Tracking', 'independent-analytics') }}</h1>
            <a class="link-purple open-report" href="{{ esc_url(iawp_dashboard_url(['tab' => 'clicks'])) }}"><span class="dashicons dashicons-analytics"></span> {{ esc_html__('View Clicks Report', 'independent-analytics') }}</a>
            <a class="link-purple" href="https://independentwp.com/knowledgebase/click-tracking/click-tracking/" target="_blank"><span class="dashicons dashicons-book"></span> {{ esc_html__('Read Tutorial', 'independent-analytics') }}</a>
        </div>
        <div id="click-tracking-cache-message-container" class="@if($show_click_tracking_cache_message) show @endif">
            <div class="cache-note">
                <span class="dashicons dashicons-warning"></span>
                <p>{{ esc_html('Please empty your cache to ensure your newest changes are tracked properly.', 'independent-analytics') }}</p>
                <button id="click-tracking-cache-cleared" class="iawp-button">{{ esc_html('Ok', 'independent-analytics') }}</button>
            </div>
        </div>
        <div id="validation-error-messages" class="validation-error-messages">
            @foreach ($error_messages as $class => $message)
                <p class="{{ esc_attr($class) }}"><span class="dashicons dashicons-warning"></span> {{ esc_html($message) }}</p>
            @endforeach
        </div>
        <div class="tracked-links click-tracking-section">
            <div class="heading-container">
                <div>
                    <h2>{{ esc_html__('Link Patterns', 'independent-analytics') }}</h2>
                    <p class="description">
                        {{ esc_html__('Links matching the patterns below are being actively monitored for clicks.', 'independent-analytics') }}
                    </p>
                </div>
                <button id="create-new-link" class="create-new-link iawp-button purple">{{ esc_html__('Add Link Pattern', 'independent-analytics') }}</button>
            </div>
            <div class="table-labels">
                <span>{{ esc_html__('Name', 'independent-analytics') }}</span>
                <span>{{ esc_html__('Type', 'independent-analytics') }}</span>
                <span>{{ esc_html__('Value', 'independent-analytics') }}</span>
                <button class="edit-button-for-spacing">{{ esc_html__('Edit', 'independent-analytics') }}</button>
                <button class="edit-button-for-spacing">{{ esc_html__('Archive', 'independent-analytics') }}</button>
            </div>
            <div id="tracked-links-list" class="tracked-links-list">
                <div id="sortable-tracked-links-list">
                    <?php
                    foreach($active_links as $link) {
                        echo iawp_blade()->run('click-tracking.link', [
                            'link' => $link,
                            'types' => $types,
                            'extensions' => $extensions,
                            'protocols' => $protocols
                        ]);
                    } ?>
                </div>
            </div>
            <p class="tracked-links-empty-message {{ count($active_links) === 0 ? "show" : "" }}">{{ esc_html('No link patterns found', 'independent-analytics') }}</p>
            <div id="blueprint-link" class="blueprint-link"><?php 
                echo iawp_blade()->run('click-tracking.link', [
                    'link' => [
                        'id' => null,
                        'name' => '',
                        'type' => 'class',
                        'value' => '',
                        'is_active' => null
                    ],
                    'types' => $types,
                    'extensions' => $extensions,
                    'protocols' => $protocols
                ]); ?>
            </div>
        </div>
        <div id="archived-links" class="archived-links click-tracking-section">
            <h2>
                {{ esc_html__('Archived Link Patterns', 'independent-analytics') }}
            </h2>
            <p class="description">
                {{ esc_html__('Archived link patterns are no longer tracked, but their data remains in the Clicks report. Deleting an archived link pattern will remove it from this list and remove its data from the Clicks report permanently.', 'independent-analytics') }}
            </p>
            <button id="toggle-archived-links" class="iawp-button toggle-archived-links" data-alt-text="{{ __('Hide Archived Links', 'independent-analytics') }}">{{ __('Show Archived Links', 'independent-analytics') }}</button>
            <div class="archived-links-table">
                <div class="table-labels">
                    <span>{{ esc_html__('Name', 'independent-analytics') }}</span>
                    <span>{{ esc_html__('Type', 'independent-analytics') }}</span>
                    <span>{{ esc_html__('Value', 'independent-analytics') }}</span>
                    <button class="edit-button-for-spacing">{{ esc_html__('Resume Tracking', 'independent-analytics') }}</button>
                    <button class="edit-button-for-spacing">{{ esc_html__('Delete', 'independent-analytics') }}</button>
                </div>
                <div id="archived-links-list" class="archived-links-list"><?php
                    foreach($inactive_links as $link) {
                        echo iawp_blade()->run('click-tracking.link', [
                            'link' => $link,
                            'types' => $types,
                            'extensions' => $extensions,
                            'protocols' => $protocols
                        ]);
                    } ?>
                </div>
                <p class="archived-links-empty-message {{ count($inactive_links) === 0 ? "show" : "" }}">{{ esc_html('No archived link patterns found', 'independent-analytics') }}</p>
            </div>
        </div>
    </div>
    <div id="delete-link-modal" aria-hidden="true" class="mm micromodal-slide delete-link-modal">
        <div tabindex="-1" class="mm__overlay" >
            <div role="dialog" aria-modal="true" class="mm__container">
                <div class="modal-title">{{ esc_html__('Are you sure?', 'independent-analytics') }}</div>
                <p>{{ esc_html__('Deleting this link pattern will remove its stats from the Clicks report permanently.', 'independent-analytics') }}</p>
                <button data-link-id="" class="iawp-button purple yes">{{ esc_html__('Yes', 'independent-analytics') }}</button>
                <button class="iawp-button ghost-purple cancel" data-micromodal-close>{{ esc_html__('Cancel', 'independent-analytics') }}</button>
            </div>
        </div>
    </div>
</div>