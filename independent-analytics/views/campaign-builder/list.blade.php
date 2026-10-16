<?php if (count($campaigns) > 0): ?>
    <?php foreach ($campaigns as $campaign_index => $campaign): ?>
        <?php echo iawp_render('campaign-builder.item', [
            'campaign'        => $campaign,
            'campaign_number' => $campaign_index + 1,
        ]); ?>
    <?php endforeach; ?>
<?php elseif ($has_filters && $has_campaigns): ?>
    <div class="campaigns-empty-state">
        <h2><?php esc_html_e('No Matching Campaigns', 'independent-analytics'); ?></h2>
        <p><?php esc_html_e('No campaigns match your current filters', 'independent-analytics'); ?></p>
        <button type="button"
                class="iawp-button"
                data-action="campaign-builder-list#clearFilters"
        >
            <?php esc_html_e('Remove All Filters', 'independent-analytics'); ?>
        </button>
    </div>
<?php else: ?>
    <div class="campaigns-empty-state">
        <h2><?php esc_html_e('No Campaigns Found', 'independent-analytics'); ?></h2>
        <p>
            <?php esc_html_e('Create a campaign to start tracking the performance of your ads and promotions', 'independent-analytics'); ?>
        </p>
        <button type="button"
                class="iawp-button"
                data-action="campaign-builder#openCreate"
        >
            <?php esc_html_e('Create New Campaign', 'independent-analytics'); ?>
        </button>
    </div>
<?php endif; ?>
