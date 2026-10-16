<?php
$campaigns            = $campaigns ?? [];
$has_campaigns        = $has_campaigns ?? count($campaigns) > 0;
$has_filters          = $has_filters ?? false;
$campaign_count       = count($campaigns);
$autocomplete_actions = 'focusin->campaign-autocomplete#focus focusout->campaign-autocomplete#blur input->campaign-autocomplete#search keydown->campaign-autocomplete#keydown mousedown->campaign-autocomplete#selectOption click@document->campaign-autocomplete#handleDocumentClick';
?>

<div class="campaign-builder"
     data-controller="campaign-builder campaign-qr-code"
     data-action="keydown@document->campaign-builder#guardModalClose:capture
                  keydown@document->campaign-qr-code#guardModalClose:capture
                  campaign-builder-item:edit->campaign-builder#openEdit
                  campaign-builder-item:qr-code->campaign-qr-code#open"
>
    <?php echo iawp_render('campaign-builder.editor', [
        'autocomplete_actions' => $autocomplete_actions,
    ]); ?>
    <?php echo iawp_render('campaign-builder.qr-code'); ?>

    <div class="campaign-builder-list"
         data-controller="campaign-builder-list"
         data-campaign-builder-list-count-value="<?php echo esc_attr($campaign_count); ?>"
         data-action="click@document->campaign-builder-list#handleDocumentClick
                      keydown@document->campaign-builder-list#handleDocumentKeydown
                      campaign-autocomplete:selected->campaign-builder-list#addAutocompleteFilter
                      campaign-builder-list:refresh@document->campaign-builder-list#refresh
                      campaign-builder-item:deleted@document->campaign-builder-list#refreshCurrent
                      campaign-builder-item:duplicated@document->campaign-builder-list#campaignDuplicated
                      campaign-builder:created@document->campaign-builder-list#campaignCreated
                      campaign-builder:updated@document->campaign-builder-list#campaignUpdated"
    >
        <div class="campaign-builder-title">
            <h1><?php esc_html_e('Campaign Builder', 'independent-analytics'); ?></h1>
            <a class="tutorial-link"
               href="https://independentwp.com/knowledgebase/campaigns/how-to-campaign-builder"
               target="_blank"
               rel="noopener noreferrer"
            ><?php esc_html_e('Read Tutorial', 'independent-analytics'); ?></a>
        </div>
        <div class="campaign-builder-actions">
            <div class="campaign-builder-actions-inner">
                <div class="campaign-builder-primary-actions">
                    <button type="button"
                            class="iawp-button purple campaign-builder-create"
                            data-action="campaign-builder#openCreate"
                    >
                        <span class="dashicons dashicons-plus-alt2"></span>
                        <?php esc_html_e('Create New Campaign', 'independent-analytics'); ?>
                    </button>
                </div>
                <div class="campaign-filter-toolbar">
                    <button type="button"
                            class="iawp-button ghost-purple campaign-filter-button"
                            aria-controls="campaign-filter-popover"
                            aria-expanded="false"
                            aria-haspopup="dialog"
                            data-campaign-builder-list-target="filterButton"
                            data-action="campaign-builder-list#toggleFilterPopover"
                    >
                        <span class="dashicons dashicons-filter"></span>
                        <?php esc_html_e('Add Filter', 'independent-analytics'); ?>
                    </button>
                    <div id="campaign-filter-popover"
                         class="campaign-filter-popover"
                         role="dialog"
                         aria-label="<?php esc_attr_e('Filter campaigns', 'independent-analytics'); ?>"
                         data-campaign-builder-list-target="filterPopover"
                         hidden
                    >
                        <div class="campaign-filter-entry">
                            <div class="campaign-autocomplete campaign-autocomplete-all"
                                 data-controller="campaign-autocomplete"
                                 data-campaign-builder-list-target="filterAutocomplete"
                                 data-action="<?php echo esc_attr($autocomplete_actions); ?> campaign-autocomplete:reset->campaign-autocomplete#reset"
                            >
                                <input type="text"
                                       id="iawp_campaign_filter"
                                       placeholder="<?php esc_attr_e('Search campaigns', 'independent-analytics'); ?>"
                                       aria-label="<?php esc_attr_e('Search campaigns', 'independent-analytics'); ?>"
                                       autocomplete="off"
                                       autocapitalize="none"
                                       autocorrect="off"
                                       spellcheck="false"
                                       data-campaign-autocomplete-target="input"
                                       data-campaign-builder-list-target="input"
                                />
                                <div class="campaign-autocomplete-listbox"
                                     data-campaign-autocomplete-target="listbox"
                                     tabindex="-1"
                                     hidden
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button"
                        class="iawp-button ghost-purple campaign-builder-export"
                        data-campaign-builder-list-target="exportButton"
                        data-action="campaign-builder-list#exportCampaignUrls"
                        data-default-label="<?php esc_attr_e('Export All Campaigns', 'independent-analytics'); ?>"
                        data-singular-label-template="<?php esc_attr_e('Export %s Campaign', 'independent-analytics'); ?>"
                        data-plural-label-template="<?php esc_attr_e('Export %s Campaigns', 'independent-analytics'); ?>"
                        <?php disabled($campaign_count, 0); ?>
                >
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <span data-campaign-builder-list-target="exportButtonLabel">
                        <?php esc_html_e('Export All Campaigns', 'independent-analytics'); ?>
                    </span>
                </button>
            </div>
        </div>
        <div class="campaign-builder-results">
            <div class="campaign-builder-results-inner">
                <div class="campaign-active-filters"
                     data-campaign-builder-list-target="activeFilters"
                     hidden
                >
                    <div class="campaign-filter-chips"
                         data-campaign-builder-list-target="filterChips"
                         data-any-field-label="<?php echo esc_attr_x('Any Field', 'Any Field contains value', 'independent-analytics'); ?>"
                         data-contains-label="<?php echo esc_attr_x('contains', 'Any Field contains value', 'independent-analytics'); ?>"
                         data-is-label="<?php echo esc_attr_x('is', 'Field is value', 'independent-analytics'); ?>"
                    ></div>
                </div>
                <div class="campaign-filter-count"
                     data-campaign-builder-list-target="resultCount"
                     data-template="<?php echo esc_attr__('Showing %1$s of %2$s campaigns', 'independent-analytics'); ?>"
                >
                    <?php printf(
                        esc_html__('Showing %1$s of %2$s campaigns', 'independent-analytics'),
                        esc_html(number_format_i18n($campaign_count)),
                        esc_html(number_format_i18n($campaign_count))
                    ); ?>
                </div>
                <div class="campaign-builder-list-content"
                     data-campaign-builder-list-target="content"
                >
                    <?php echo iawp_render('campaign-builder.list', [
                        'campaigns'     => $campaigns,
                        'has_campaigns' => $has_campaigns,
                        'has_filters'   => $has_filters,
                    ]); ?>
                </div>
            </div>
        </div>
        <div class="campaign-builder-list-error"
             data-campaign-builder-list-target="error"
             hidden
        >
            <span><?php esc_html_e('Campaign URLs could not be refreshed.', 'independent-analytics'); ?></span>
            <button type="button"
                    class="iawp-button ghost-purple"
                    data-action="campaign-builder-list#retry"
            >
                <?php esc_html_e('Retry', 'independent-analytics'); ?>
            </button>
        </div>
    </div>
</div>
