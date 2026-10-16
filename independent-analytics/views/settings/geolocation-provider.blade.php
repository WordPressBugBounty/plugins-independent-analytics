<div class="geolocation-provider-settings settings-container">
    <div class="heading">
        <h2><?php esc_html_e('Geolocation Provider', 'independent-analytics'); ?></h2>
        <a class="tutorial-link" href="https://independentwp.com/knowledgebase/tracking/cloudflare-geolocation" target="_blank">
            <?php esc_html_e('Read Tutorial', 'independent-analytics'); ?>
        </a>
    </div>

    <?php if ($provider === 'database'): ?>
        <div class="schedule-notification is-not-scheduled">
            <span class="dashicons dashicons-info-outline"></span>
            <p><?php esc_html_e('Cloudflare geolocation headers are available. Switch to Cloudflare to use them for visitor geolocation.', 'independent-analytics'); ?></p>
        </div>
    <?php elseif ($cloudflare_available): ?>
        <div class="schedule-notification is-scheduled">
            <span class="dashicons dashicons-yes-alt"></span>
            <p><?php esc_html_e('Geolocations are being tracked with Cloudflare.', 'independent-analytics'); ?></p>
        </div>
    <?php else: ?>
        <div class="schedule-notification is-error">
            <span class="dashicons dashicons-warning"></span>
            <p><?php esc_html_e('Cloudflare geolocation headers are missing. Geolocation data is not being tracked.', 'independent-analytics'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php settings_fields('iawp_geolocation_settings'); ?>

        <fieldset class="geolocation-provider-options">
            <label for="iawp_geo_provider_database">
                <input type="radio"
                       name="iawp_geo_provider"
                       id="iawp_geo_provider_database"
                       value="database" <?php checked('database', $provider, true); ?>>
                <span>
                    <strong><?php esc_html_e('Local Database', 'independent-analytics'); ?></strong>
                    <span class="description"><?php esc_html_e('Use a local geolocation database to track visitor locations. This database is stored in the WordPress uploads directory.', 'independent-analytics'); ?></span>
                </span>
            </label>

            <label for="iawp_geo_provider_cloudflare">
                <input type="radio"
                       name="iawp_geo_provider"
                       id="iawp_geo_provider_cloudflare"
                       value="cloudflare" <?php checked('cloudflare', $provider, true); ?>>
                <span>
                    <strong><?php esc_html_e('Cloudflare', 'independent-analytics'); ?></strong>
                    <span class="description"><?php esc_html_e('Use Cloudflare geolocation headers to track visitor locations.', 'independent-analytics'); ?></span>
                </span>
            </label>
        </fieldset>

        <?php if ($is_multisite): ?>
        <?php endif; ?>

        <?php submit_button(esc_html__('Save', 'independent-analytics'), 'iawp-button purple', 'save-geolocation-provider'); ?>
    </form>
</div>
