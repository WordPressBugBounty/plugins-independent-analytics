<?php $editor_autocomplete_actions = $autocomplete_actions . ' campaign-autocomplete:reset->campaign-autocomplete#reset'; ?>

<div id="iawp-campaign-editor-modal"
     class="mm micromodal-slide campaign-editor-modal"
     data-campaign-builder-target="modal"
>
    <div class="mm__overlay campaign-editor-overlay"
         tabindex="-1"
         data-action="click->campaign-builder#closeFromOverlay"
    >
        <div class="mm__container campaign-editor-container">
            <div class="campaign-editor-header">
                <div>
                    <h2 data-campaign-builder-target="title"
                        data-create-label="<?php esc_attr_e('Create New Campaign', 'independent-analytics'); ?>"
                        data-update-label="<?php esc_attr_e('Update Campaign', 'independent-analytics'); ?>"
                    >
                        <?php esc_html_e('Create New Campaign', 'independent-analytics'); ?>
                    </h2>
                </div>
                <button type="button"
                        class="campaign-editor-close"
                        data-micromodal-close
                        data-campaign-builder-target="closeButton"
                >
                    <span>&times;</span>
                    <span class="screen-reader-text"><?php esc_html_e('Close', 'independent-analytics'); ?></span>
                </button>
            </div>

            <form class="campaign-editor-form"
                  data-action="submit->campaign-builder#submit input->campaign-builder#handleInput"
                  data-campaign-builder-target="form"
            >
                <div class="campaign-editor-body">
                    <fieldset data-campaign-builder-target="fields">
                        <div class="campaign-editor-fields">
                            <div class="campaign-editor-field">
                                <label for="iawp_site_url"><?php esc_html_e('Site URL', 'independent-analytics'); ?></label>
                                <input type="text"
                                       name="site_url"
                                       id="iawp_site_url"
                                       value="<?php echo esc_attr(trailingslashit(home_url())); ?>"
                                       disabled
                                />
                                <p class="description"><?php esc_html_e('Campaign links always lead to your site', 'independent-analytics'); ?></p>
                            </div>

                            <div class="campaign-editor-field">
                                <label for="iawp_path"><?php esc_html_e('Landing Page Path', 'independent-analytics'); ?></label>
                                <div class="campaign-autocomplete"
                                     data-controller="campaign-autocomplete"
                                     data-campaign-autocomplete-field-value="path"
                                     data-action="<?php echo esc_attr($editor_autocomplete_actions); ?>"
                                >
                                    <input type="text"
                                           name="path"
                                           id="iawp_path"
                                           placeholder="blog/some-post"
                                           autocomplete="off"
                                           data-1p-ignore
                                           data-campaign-autocomplete-target="input"
                                    />
                                    <div class="campaign-autocomplete-listbox"
                                         data-campaign-autocomplete-target="listbox"
                                         tabindex="-1"
                                         hidden
                                    ></div>
                                </div>
                                <p class="description"><?php esc_html_e('Leave empty to use your homepage', 'independent-analytics'); ?></p>
                                <p class="error campaign-editor-field-error" data-campaign-builder-error-field="path" hidden></p>
                            </div>

                            <div class="campaign-editor-field">
                                <label for="iawp_utm_source"><?php esc_html_e('Source', 'independent-analytics'); ?><span class="required">*</span></label>
                                <div class="campaign-autocomplete"
                                     data-controller="campaign-autocomplete"
                                     data-campaign-autocomplete-field-value="utm_source"
                                     data-action="<?php echo esc_attr($editor_autocomplete_actions); ?>"
                                >
                                    <input type="text"
                                           name="utm_source"
                                           id="iawp_utm_source"
                                           placeholder="Twitter"
                                           autocomplete="off"
                                           data-campaign-autocomplete-target="input"
                                    />
                                    <div class="campaign-autocomplete-listbox"
                                         data-campaign-autocomplete-target="listbox"
                                         tabindex="-1"
                                         hidden
                                    ></div>
                                </div>
                                <p class="description"><?php esc_html_e('Name of the website the link will be placed on', 'independent-analytics'); ?></p>
                                <p class="error campaign-editor-field-error" data-campaign-builder-error-field="utm_source" hidden></p>
                            </div>

                            <div class="campaign-editor-field">
                                <label for="iawp_utm_medium"><?php esc_html_e('Medium', 'independent-analytics'); ?> <span class="required">*</span></label>
                                <div class="campaign-autocomplete"
                                     data-controller="campaign-autocomplete"
                                     data-campaign-autocomplete-field-value="utm_medium"
                                     data-action="<?php echo esc_attr($editor_autocomplete_actions); ?>"
                                >
                                    <input type="text"
                                           name="utm_medium"
                                           id="iawp_utm_medium"
                                           placeholder="<?php esc_attr_e('Social Media', 'independent-analytics'); ?>"
                                           autocomplete="off"
                                           data-campaign-autocomplete-target="input"
                                    />
                                    <div class="campaign-autocomplete-listbox"
                                         data-campaign-autocomplete-target="listbox"
                                         tabindex="-1"
                                         hidden
                                    ></div>
                                </div>
                                <p class="description"><?php esc_html_e('Type of website e.g. Search, Social, Ad', 'independent-analytics'); ?></p>
                                <p class="error campaign-editor-field-error" data-campaign-builder-error-field="utm_medium" hidden></p>
                            </div>

                            <div class="campaign-editor-field">
                                <label for="iawp_utm_campaign"><?php esc_html_e('Campaign', 'independent-analytics'); ?> <span class="required">*</span></label>
                                <div class="campaign-autocomplete"
                                     data-controller="campaign-autocomplete"
                                     data-campaign-autocomplete-field-value="utm_campaign"
                                     data-action="<?php echo esc_attr($editor_autocomplete_actions); ?>"
                                >
                                    <input type="text"
                                           name="utm_campaign"
                                           id="iawp_utm_campaign"
                                           placeholder="<?php esc_attr_e('5 Ways to Get More Traffic', 'independent-analytics'); ?>"
                                           autocomplete="off"
                                           data-campaign-autocomplete-target="input"
                                    />
                                    <div class="campaign-autocomplete-listbox"
                                         data-campaign-autocomplete-target="listbox"
                                         tabindex="-1"
                                         hidden
                                    ></div>
                                </div>
                                <p class="description"><?php esc_html_e('Title of the page or ad', 'independent-analytics'); ?></p>
                                <p class="error campaign-editor-field-error" data-campaign-builder-error-field="utm_campaign" hidden></p>
                            </div>

                            <div class="campaign-editor-field">
                                <label for="iawp_utm_term"><?php esc_html_e('Term', 'independent-analytics'); ?></label>
                                <div class="campaign-autocomplete"
                                     data-controller="campaign-autocomplete"
                                     data-campaign-autocomplete-field-value="utm_term"
                                     data-action="<?php echo esc_attr($editor_autocomplete_actions); ?>"
                                >
                                    <input type="text"
                                           name="utm_term"
                                           id="iawp_utm_term"
                                           placeholder="<?php esc_attr_e('Get website traffic', 'independent-analytics'); ?>"
                                           autocomplete="off"
                                           data-campaign-autocomplete-target="input"
                                    />
                                    <div class="campaign-autocomplete-listbox"
                                         data-campaign-autocomplete-target="listbox"
                                         tabindex="-1"
                                         hidden
                                    ></div>
                                </div>
                                <p class="description"><?php esc_html_e('Keyword used in paid advertising', 'independent-analytics'); ?></p>
                            </div>

                            <div class="campaign-editor-field">
                                <label for="iawp_utm_content"><?php esc_html_e('Content', 'independent-analytics'); ?></label>
                                <div class="campaign-autocomplete"
                                     data-controller="campaign-autocomplete"
                                     data-campaign-autocomplete-field-value="utm_content"
                                     data-action="<?php echo esc_attr($editor_autocomplete_actions); ?>"
                                >
                                    <input type="text"
                                           name="utm_content"
                                           id="iawp_utm_content"
                                           placeholder="<?php esc_attr_e('Bio link', 'independent-analytics'); ?>"
                                           autocomplete="off"
                                           data-campaign-autocomplete-target="input"
                                    />
                                    <div class="campaign-autocomplete-listbox"
                                         data-campaign-autocomplete-target="listbox"
                                         tabindex="-1"
                                         hidden
                                    ></div>
                                </div>
                                <p class="description"><?php esc_html_e('Position of the link on the page e.g. author bio', 'independent-analytics'); ?></p>
                            </div>
                        </div>
                    </fieldset>

                    <div class="campaign-editor-field campaign-editor-note-field">
                        <label for="iawp_campaign_note"><?php esc_html_e('Private Note', 'independent-analytics'); ?></label>
                        <input type="text"
                               name="note"
                               id="iawp_campaign_note"
                        />
                        <p class="description"><?php esc_html_e('This note is private and only visible in the Campaign Builder', 'independent-analytics'); ?></p>
                        <p class="error campaign-editor-field-error" data-campaign-builder-error-field="note" hidden></p>
                    </div>

                    <div class="campaign-editor-request-error"
                         data-campaign-builder-target="requestError"
                         data-create-message="<?php esc_attr_e('The campaign could not be created. Please try again.', 'independent-analytics'); ?>"
                         data-update-message="<?php esc_attr_e('The campaign could not be updated. Please try again.', 'independent-analytics'); ?>"
                         hidden
                    >
                        <?php esc_html_e('The campaign could not be created. Please try again.', 'independent-analytics'); ?>
                    </div>
                </div>

                <div class="campaign-editor-footer">
                    <button type="button"
                            class="iawp-button ghost-purple"
                            data-micromodal-close
                            data-campaign-builder-target="cancelButton"
                    >
                        <?php esc_html_e('Cancel', 'independent-analytics'); ?>
                    </button>
                    <button type="submit"
                            class="iawp-button purple"
                            data-campaign-builder-target="submitButton"
                            data-create-label="<?php esc_attr_e('Create New Campaign', 'independent-analytics'); ?>"
                            data-update-label="<?php esc_attr_e('Update Campaign', 'independent-analytics'); ?>"
                    >
                        <?php esc_html_e('Create New Campaign', 'independent-analytics'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
