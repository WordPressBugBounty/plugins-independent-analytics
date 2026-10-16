<?php $campaign_actions_id = 'campaign-actions-' . $campaign['campaign_url_id']; ?>
<div class="campaign"
     data-controller="campaign-builder-item"
     data-campaign-url-id="<?php echo esc_attr($campaign['campaign_url_id']); ?>"
     data-campaign-fields="<?php echo esc_attr(wp_json_encode($campaign['editor_fields'])); ?>"
     data-action="campaign-builder-item:opened@document->campaign-builder-item#handlePanelOpened
                  click@document->campaign-builder-item#handleDocumentClick
                  keydown@document->campaign-builder-item#handleKeydown
                  resize@window->campaign-builder-item#positionPanel
                  scroll@window->campaign-builder-item#positionPanel:capture"
>
    <div class="campaign-copy">
        <div class="campaign-url">
            <div>
                <?php  
                $url = esc_html($campaign['url']);
                echo preg_replace(
                    '/([?&](?:amp;)?utm_[\w-]+=)([^&#]*)/i',
                    '<span class="utm-param">$1</span><span class="utm-value">$2</span>',
                    $url
                );
                ?>
            </div>
        </div>
        <div class="campaign-footer">
            <div class="campaign-actions">
                <button type="button"
                        class="campaign-row-button campaign-copy-button"
                        title="<?php esc_attr_e('Copy URL', 'independent-analytics'); ?>"
                        data-action="campaign-builder-item#copyCampaignUrl"
                        data-copy-text="<?php echo esc_attr($campaign['url']); ?>"
                >
                    <svg class="campaign-copy-icon"
                         viewBox="0 0 20 20"
                         focusable="false"
                         data-campaign-builder-item-target="copyIcon"
                    >
                        <rect x="7" y="7" width="10" height="10" rx="1.5"></rect>
                        <path d="M13 7V4.5A1.5 1.5 0 0 0 11.5 3h-7A1.5 1.5 0 0 0 3 4.5v7A1.5 1.5 0 0 0 4.5 13H7"></path>
                    </svg>
                    <svg class="campaign-copy-success-icon"
                         viewBox="0 0 20 20"
                         focusable="false"
                         data-campaign-builder-item-target="copySuccessIcon"
                         hidden
                    >
                        <path d="M3.5 10.5 8 15l8.5-10"></path>
                    </svg>
                </button>
                <a class="campaign-row-button campaign-open-button"
                   href="<?php echo esc_attr($campaign['url']); ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   title="<?php esc_attr_e('Open URL in new tab', 'independent-analytics'); ?>"
                >
                    <svg class="campaign-open-icon"
                         viewBox="0 0 20 20"
                         focusable="false"
                    >
                        <path d="M8 4H5.5A1.5 1.5 0 0 0 4 5.5v9A1.5 1.5 0 0 0 5.5 16h9a1.5 1.5 0 0 0 1.5-1.5V12"></path>
                        <path d="M11 3h6v6"></path>
                        <path d="m9 11 8-8"></path>
                    </svg>
                </a>
                <button type="button"
                        class="campaign-row-button campaign-qr-button"
                        title="<?php esc_attr_e('Download QR Code', 'independent-analytics'); ?>"
                        aria-label="<?php esc_attr_e('Download QR Code', 'independent-analytics'); ?>"
                        data-action="campaign-builder-item#downloadQrCode"
                        data-campaign-url="<?php echo esc_attr($campaign['url']); ?>"
                        data-qr-label="<?php esc_attr_e('Download QR Code', 'independent-analytics'); ?>"
                        data-qr-error-label="<?php esc_attr_e('Could not load. Try again.', 'independent-analytics'); ?>"
                >
                    <svg class="campaign-qr-icon"
                         viewBox="0 0 20 20"
                         aria-hidden="true"
                         focusable="false"
                    >
                        <path d="M6.75 1.25h-5.5v5.5M13.25 1.25h5.5v5.5M18.75 13.25v5.5h-5.5M6.75 18.75h-5.5v-5.5"></path>
                        <rect class="campaign-qr-finder" x="5.25" y="5.25" width="3.5" height="3.5" rx="0.9"></rect>
                        <rect class="campaign-qr-finder" x="11.25" y="5.25" width="3.5" height="3.5" rx="0.9"></rect>
                        <rect class="campaign-qr-finder" x="5.25" y="11.25" width="3.5" height="3.5" rx="0.9"></rect>
                        <path class="campaign-qr-modules" d="M11.25 11.25h1.5v1.5h-1.5zM14 11.25h1.5v1.5H14zM14 14h1.5v1.5H14z"></path>
                    </svg>
                </button>
                <button type="button"
                        class="campaign-row-button campaign-actions-toggle"
                        data-campaign-builder-item-target="trigger"
                        data-action="campaign-builder-item#toggleActions"
                >
                    <span>&hellip;</span>
                </button>
                <?php if ($campaign['note'] !== ''): ?>
                    <p class="campaign-note">
                        <span class="dashicons dashicons-text-page"></span>
                        <span><?php echo esc_html($campaign['note']); ?></span>
                    </p>
                <?php endif; ?>
                <div id="<?php echo esc_attr($campaign_actions_id); ?>"
                     class="campaign-actions-menu"
                     data-campaign-builder-item-target="panel"
                     hidden
                >
                    <ul>
                        <li>
                            <button type="button"
                                    data-action="campaign-builder-item#edit"
                            >
                                <?php esc_html_e('Edit', 'independent-analytics'); ?>
                            </button>
                        </li>
                        <li>
                            <button type="button"
                                    data-action="campaign-builder-item#duplicate"
                            >
                                <?php esc_html_e('Duplicate', 'independent-analytics'); ?>
                            </button>
                        </li>
                        <li>
                            <button type="button"
                                    class="delete"
                                    data-campaign-builder-item-target="delete"
                                    data-confirm-label="<?php esc_attr_e('Confirm Delete', 'independent-analytics'); ?>"
                                    data-campaign-url-id="<?php echo esc_attr($campaign['campaign_url_id']); ?>"
                                    data-action="campaign-builder-item#delete"
                            >
                                <?php esc_html_e('Delete', 'independent-analytics'); ?>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="campaign-created-at">
                <span><?php printf(esc_html_x('Created %1$s (%2$s)', 'Created July 24, 2026 (five minutes ago)', 'independent-analytics'), esc_html($campaign['created_at']), esc_html($campaign['created_ago'])); ?></span>
            </div>
        </div>
    </div>
</div>
