<div class="iawp-notice <?php echo esc_attr($notice) . ' ' . esc_attr($plugin); ?>" data-testid="{{ esc_attr($id) }}">
    <div class="iawp-icon">
        <span class="dashicons dashicons-warning"></span>
    </div>
    <div class="iawp-message">
        <p><span class="iawp-message-text"><?php echo wp_kses_post($notice_text); ?></span> <a href="<?php echo esc_url($url); ?>" class="link-white" target="_blank"><?php esc_html_e('Learn More', 'independent-analytics'); ?></a></p>
    </div>
    <?php if ($button_text) : ?>
        <div>
            <button class="iawp-button white dismiss-notice" data-notice-id="{{ esc_attr($id) }}"><?php echo esc_html($button_text); ?></button>
        </div>
    <?php endif; ?>
</div>