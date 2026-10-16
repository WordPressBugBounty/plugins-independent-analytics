<div id="iawp-campaign-qr-code-modal" class="mm micromodal-slide campaign-qr-modal" aria-hidden="true"
    data-campaign-qr-code-target="modal" data-media-title="<?php esc_attr_e('Choose a QR code logo', 'independent-analytics'); ?>"
    data-media-button-label="<?php esc_attr_e('Use as Logo', 'independent-analytics'); ?>" data-media-library-message="<?php esc_attr_e('The Media Library is unavailable. Please reload and try again.', 'independent-analytics'); ?>"
    data-generation-message="<?php esc_attr_e('The QR code could not be generated. Please try again.', 'independent-analytics'); ?>" data-download-message="<?php esc_attr_e('The QR code could not be downloaded. Please try again.', 'independent-analytics'); ?>">
    <div class="mm__overlay campaign-editor-overlay campaign-qr-overlay" tabindex="-1"
        data-action="click->campaign-qr-code#closeFromOverlay">
        <div class="mm__container campaign-editor-container campaign-qr-container" role="dialog" aria-modal="true"
            aria-labelledby="iawp-campaign-qr-code-title">
            <div class="campaign-editor-header">
                <h2 id="iawp-campaign-qr-code-title"><?php esc_html_e('Download QR Code', 'independent-analytics'); ?></h2>
                <button type="button" class="campaign-editor-close" data-micromodal-close
                    data-campaign-qr-code-target="closeButton">
                    <span aria-hidden="true">&times;</span>
                    <span class="screen-reader-text"><?php esc_html_e('Close', 'independent-analytics'); ?></span>
                </button>
            </div>

            <form class="campaign-qr-form" data-campaign-qr-code-target="form"
                data-action="submit->campaign-qr-code#preventSubmission input->campaign-qr-code#handleInput change->campaign-qr-code#handleChange focusout->campaign-qr-code#normalizeColor">
                <div class="campaign-qr-body">
                    <div class="campaign-qr-preview-column">
                        <div class="campaign-qr-preview-stage">
                            <div class="campaign-qr-preview" aria-label="<?php esc_attr_e('QR code preview', 'independent-analytics'); ?>" aria-live="polite"
                                data-campaign-qr-code-target="preview"></div>
                        </div>
                        <p class="campaign-qr-url" data-campaign-qr-code-target="url"></p>
                        <p class="auto-save-note">
                            <span class="dashicons dashicons-info"></span>
                            <?php esc_html_e('Changes made here are auto-saved and reused for all campaigns.', 'independent-analytics'); ?>
                        </p>
                        <p class="campaign-qr-error" role="alert" data-campaign-qr-code-target="error" hidden></p>
                    </div>

                    <div class="campaign-qr-controls">
                        <div class="campaign-qr-field">
                            <label for="iawp_campaign_qr_size"><?php esc_html_e('Size', 'independent-analytics'); ?></label>
                            <div class="campaign-qr-field-content">
                                <select id="iawp_campaign_qr_size" name="size">
                                    <option value="256" selected>256 &times; 256</option>
                                    <option value="512">512 &times; 512</option>
                                    <option value="1024">1024 &times; 1024</option>
                                </select>
                            </div>
                        </div>

                        <fieldset class="campaign-qr-fieldset">
                            <legend><?php esc_html_e('Pattern style', 'independent-analytics'); ?></legend>
                            <div class="campaign-qr-field-content">
                                <div class="campaign-qr-choices">
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="pattern" value="square" checked />
                                        <span><?php esc_html_e('Square', 'independent-analytics'); ?></span>
                                    </label>
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="pattern" value="rounded" />
                                        <span><?php esc_html_e('Rounded', 'independent-analytics'); ?></span>
                                    </label>
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="pattern" value="dots" />
                                        <span><?php esc_html_e('Dots', 'independent-analytics'); ?></span>
                                    </label>
                                </div>
                                <div class="campaign-qr-color" data-qr-color="pattern">
                                    <label for="iawp_campaign_qr_pattern_color"><?php esc_html_e('Pattern color', 'independent-analytics'); ?></label>
                                    <div>
                                        <input type="color" id="iawp_campaign_qr_pattern_color" value="#000000"
                                            aria-label="<?php esc_attr_e('Pattern color picker', 'independent-analytics'); ?>" data-qr-color-input="picker" />
                                        <input type="text" value="#000000" maxlength="7" spellcheck="false"
                                            aria-label="<?php esc_attr_e('Pattern hex color', 'independent-analytics'); ?>" data-qr-color-input="hex" />
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="campaign-qr-fieldset">
                            <legend><?php esc_html_e('Eye style', 'independent-analytics'); ?></legend>
                            <div class="campaign-qr-field-content">
                                <div class="campaign-qr-choices">
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="eyes" value="square" checked />
                                        <span><?php esc_html_e('Square', 'independent-analytics'); ?></span>
                                    </label>
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="eyes" value="rounded" />
                                        <span><?php esc_html_e('Rounded', 'independent-analytics'); ?></span>
                                    </label>
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="eyes" value="circular" />
                                        <span><?php esc_html_e('Circular', 'independent-analytics'); ?></span>
                                    </label>
                                </div>
                                <div class="campaign-qr-color" data-qr-color="eyes">
                                    <label for="iawp_campaign_qr_eyes_color"><?php esc_html_e('Eye color', 'independent-analytics'); ?></label>
                                    <div>
                                        <input type="color" id="iawp_campaign_qr_eyes_color" value="#000000"
                                            aria-label="<?php esc_attr_e('Eye color picker', 'independent-analytics'); ?>" data-qr-color-input="picker" />
                                        <input type="text" value="#000000" maxlength="7" spellcheck="false"
                                            aria-label="<?php esc_attr_e('Eye hex color', 'independent-analytics'); ?>" data-qr-color-input="hex" />
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="campaign-qr-fieldset">
                            <legend><?php esc_html_e('Frame style', 'independent-analytics'); ?></legend>
                            <div class="campaign-qr-field-content">
                                <div class="campaign-qr-choices">
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="frame" value="none" checked />
                                        <span><?php esc_html_e('None', 'independent-analytics'); ?></span>
                                    </label>
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="frame" value="border" />
                                        <span><?php esc_html_e('Square', 'independent-analytics'); ?></span>
                                    </label>
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="frame" value="rounded" />
                                        <span><?php esc_html_e('Rounded', 'independent-analytics'); ?></span>
                                    </label>
                                </div>
                                <div class="campaign-qr-color" data-qr-color="frame" hidden>
                                    <label for="iawp_campaign_qr_frame_color"><?php esc_html_e('Frame color', 'independent-analytics'); ?></label>
                                    <div>
                                        <input type="color" id="iawp_campaign_qr_frame_color" value="#000000"
                                            aria-label="<?php esc_attr_e('Frame color picker', 'independent-analytics'); ?>" data-qr-color-input="picker" />
                                        <input type="text" value="#000000" maxlength="7" spellcheck="false"
                                            aria-label="<?php esc_attr_e('Frame hex color', 'independent-analytics'); ?>" data-qr-color-input="hex" />
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="campaign-qr-fieldset">
                            <legend><?php esc_html_e('Background style', 'independent-analytics'); ?></legend>
                            <div class="campaign-qr-field-content">
                                <div class="campaign-qr-choices">
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="background" value="white" checked />
                                        <span><?php esc_html_e('White', 'independent-analytics'); ?></span>
                                    </label>
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="background" value="none" />
                                        <span><?php esc_html_e('None', 'independent-analytics'); ?></span>
                                    </label>
                                    <label class="campaign-qr-choice">
                                        <input type="radio" name="background" value="custom" />
                                        <span><?php esc_html_e('Custom', 'independent-analytics'); ?></span>
                                    </label>
                                </div>
                                <div class="campaign-qr-color" data-qr-color="background" hidden>
                                    <label for="iawp_campaign_qr_background_color"><?php esc_html_e('Background color', 'independent-analytics'); ?></label>
                                    <div>
                                        <input type="color" id="iawp_campaign_qr_background_color" value="#ffffff"
                                            aria-label="<?php esc_attr_e('Background color picker', 'independent-analytics'); ?>" data-qr-color-input="picker" />
                                        <input type="text" value="#ffffff" maxlength="7" spellcheck="false"
                                            aria-label="<?php esc_attr_e('Background hex color', 'independent-analytics'); ?>" data-qr-color-input="hex" />
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="campaign-qr-fieldset">
                            <legend><?php esc_html_e('Logo', 'independent-analytics'); ?></legend>
                            <div class="campaign-qr-field-content">
                                <div class="campaign-qr-logo-row">
                                    <button type="button" class="iawp-button ghost-purple"
                                        data-campaign-qr-code-target="chooseLogoButton"
                                        data-action="campaign-qr-code#openMediaLibrary">
                                        <?php esc_html_e('Choose Logo', 'independent-analytics'); ?>
                                    </button>
                                    <span class="campaign-qr-logo-name" data-campaign-qr-code-target="logoName"
                                        hidden></span>
                                    <button type="button" class="campaign-qr-logo-remove"
                                        data-campaign-qr-code-target="removeLogoButton"
                                        data-action="campaign-qr-code#removeLogo" hidden>
                                        <?php esc_html_e('Remove', 'independent-analytics'); ?>
                                    </button>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="campaign-editor-footer campaign-qr-footer">
                    <button type="button" class="iawp-button ghost-purple" data-micromodal-close
                        data-campaign-qr-code-target="cancelButton">
                        <?php esc_html_e('Cancel', 'independent-analytics'); ?>
                    </button>
                    <button type="button" class="iawp-button purple"
                        data-action="campaign-qr-code#downloadPng" data-campaign-qr-code-target="downloadButton"
                        disabled>
                        <?php esc_html_e('Download PNG', 'independent-analytics'); ?>
                    </button>
                    <button type="button" class="iawp-button purple" data-action="campaign-qr-code#downloadSvg"
                        data-campaign-qr-code-target="downloadButton" disabled>
                        <?php esc_html_e('Download SVG', 'independent-analytics'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
