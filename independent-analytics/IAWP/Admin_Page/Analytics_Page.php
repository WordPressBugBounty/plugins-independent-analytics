<?php

namespace IAWP\Admin_Page;

use IAWP\Capability_Manager;
use IAWP\Chart;
use IAWP\Chart_Geo;
use IAWP\Dashboard_Options;
use IAWP\Database;
use IAWP\Env;
use IAWP\Plugin_Conflict_Detector;
use IAWP\Quick_Stats;
use IAWP\Real_Time;
use IAWP\Report_Finder;
use IAWP\Tables\Table;
use IAWP\Tables\Table_Campaigns;
use IAWP\Tables\Table_Clicks;
use IAWP\Tables\Table_Devices;
use IAWP\Tables\Table_Geo;
use IAWP\Tables\Table_Pages;
use IAWP\Tables\Table_Referrers;
use IAWP\Utils\Security;
/** @internal */
class Analytics_Page extends \IAWP\Admin_Page\Admin_Page
{
    protected function render_page()
    {
        $options = Dashboard_Options::getInstance();
        $date_rage = $options->get_date_range();
        $tab = (new Env())->get_tab();
        if ($tab === 'views') {
            $table = new Table_Pages();
            $statistics_class = $table->group()->statistics_class();
            $statistics = new $statistics_class($date_rage, null, $options->chart_interval());
            $stats = new Quick_Stats($statistics);
            $chart = new Chart($statistics);
            $this->interface($table, $stats, $chart);
        } elseif ($tab === 'referrers') {
            $table = new Table_Referrers();
            $statistics_class = $table->group()->statistics_class();
            $statistics = new $statistics_class($date_rage, null, $options->chart_interval());
            $stats = new Quick_Stats($statistics);
            $chart = new Chart($statistics);
            $this->interface($table, $stats, $chart);
        } elseif ($tab === 'geo') {
            $table = new Table_Geo($options->group());
            $statistics_class = $table->group()->statistics_class();
            $statistics = new $statistics_class($date_rage, null, $options->chart_interval());
            $stats = new Quick_Stats($statistics);
            $table_data_class = $table->group()->rows_class();
            $geo_data = new $table_data_class($date_rage);
            $chart = new Chart_Geo($geo_data->rows());
            $this->interface($table, $stats, $chart);
        } elseif ($tab === 'campaigns') {
            $table = new Table_Campaigns();
            $statistics_class = $table->group()->statistics_class();
            $statistics = new $statistics_class($date_rage, null, $options->chart_interval());
            $stats = new Quick_Stats($statistics);
            $chart = new Chart($statistics);
            $this->interface($table, $stats, $chart);
        } elseif ($tab === 'clicks') {
            $table = new Table_Clicks();
            $statistics_class = $table->group()->statistics_class();
            $statistics = new $statistics_class($date_rage, null, $options->chart_interval());
            $stats = new Quick_Stats($statistics);
            $chart = new Chart($statistics);
            $this->interface($table, $stats, $chart);
        } elseif ($tab === 'devices') {
            $table = new Table_Devices($options->group());
            $statistics_class = $table->group()->statistics_class();
            $statistics = new $statistics_class($date_rage, null, $options->chart_interval());
            $stats = new Quick_Stats($statistics);
            $chart = new Chart($statistics);
            $this->interface($table, $stats, $chart);
        } elseif ($tab === 'real-time') {
            (new Real_Time())->render_real_time_analytics();
        }
    }
    private function interface(Table $table, $stats, $chart)
    {
        $options = Dashboard_Options::getInstance();
        $sort_configuration = $table->sanitize_sort_parameters($options->sort_column(), $options->sort_direction());
        ?>
        <div data-controller="report"
             data-report-name-value="<?php 
        echo Security::string($options->report_name());
        ?>"
             data-report-relative-range-id-value="<?php 
        echo Security::attr($options->relative_range_id());
        ?>"
             data-report-exact-start-value="<?php 
        echo Security::attr($options->start());
        ?>"
             data-report-exact-end-value="<?php 
        echo Security::attr($options->end());
        ?>"
             data-report-group-value="<?php 
        echo Security::attr($options->group());
        ?>"
             data-report-filters-value="<?php 
        echo \esc_attr(Security::json_encode($options->filters()));
        ?>"
             data-report-chart-interval-value="<?php 
        echo Security::attr($options->chart_interval()->id());
        ?>"
             data-report-sort-column-value="<?php 
        echo Security::attr($sort_configuration->column());
        ?>"
             data-report-sort-direction-value="<?php 
        echo Security::attr($sort_configuration->direction());
        ?>"
             data-report-columns-value="<?php 
        echo \esc_attr(Security::json_encode($table->visible_column_ids()));
        ?>"
             data-report-quick-stats-value="<?php 
        echo \esc_attr(Security::json_encode($options->visible_quick_stats()));
        ?>"
             data-report-primary-chart-metric-id-value="<?php 
        echo \esc_attr($options->primary_chart_metric_id());
        ?>"
             data-report-secondary-chart-metric-id-value="<?php 
        echo \esc_attr($options->secondary_chart_metric_id());
        ?>"
        >
            <div id="report-header-container" class="report-header-container">
                <?php 
        echo \IAWPSCOPED\iawp_blade()->run('partials.report-header', ['report' => (new Report_Finder())->current(), 'can_edit' => Capability_Manager::can_edit()]);
        ?>
                <?php 
        $table->output_report_toolbar();
        ?>
                <div class="modal-background"></div>
            </div>
            <?php 
        echo $stats->get_html();
        ?>
            <?php 
        echo $chart->get_html();
        ?>
            <?php 
        echo $table->get_table_toolbar_markup();
        ?>
            <?php 
        echo $table->get_table_markup($sort_configuration->column(), $sort_configuration->direction());
        ?>
        </div>
        <?php 
        if (Env::get_tab() === 'geo') {
            echo '<div class="geo-ip-attribution">';
            echo \esc_html_x('Geolocation data powered by', 'Following text is a noun: DB-IP', 'independent-analytics') . ' ' . '<a href="https://db-ip.com" target="_blank">DB-IP</a>.';
            echo '</div>';
        }
        ?>
        <div class="iawp-notices"><?php 
        if (Capability_Manager::can_edit()) {
            $plugin_conflict_detector = new Plugin_Conflict_Detector();
            if ($plugin_conflict_detector->has_conflict()) {
                echo \IAWPSCOPED\iawp_blade()->run('notices.notice', ['notice_text' => $plugin_conflict_detector->get_error(), 'button_text' => \false, 'id' => 'plugin-conflict', 'notice' => 'iawp-error', 'url' => 'https://independentwp.com/knowledgebase/tracking/secure-rest-api/']);
            } elseif (\IAWPSCOPED\iawp_is_pro() && \is_plugin_active('better-wp-security/better-wp-security.php')) {
                $settings = \get_option('itsec-storage');
                if (\array_key_exists('system-tweaks', $settings)) {
                    if (\array_key_exists('plugins_php', $settings['system-tweaks'])) {
                        if ($settings['system-tweaks']['plugins_php']) {
                            echo \IAWPSCOPED\iawp_blade()->run('notices.notice', ['notice_text' => \__('The "Solid Security" plugin is disabling PHP execution in the plugins folder, and this is preventing click tracking from working. Please visit the Security > Settings page, click on the Advanced section, click on System Tweaks Settings, uncheck the "Disable PHP Plugins" option, and then save.', 'independent-analytics'), 'button_text' => \false, 'id' => 'plugin-conflict', 'notice' => 'iawp-error', 'url' => 'https://independentwp.com/knowledgebase/click-tracking/allow-php-execution-plugins-folder/']);
                        }
                    }
                }
            }
            if (\get_option('iawp_need_clear_cache')) {
                echo \IAWPSCOPED\iawp_blade()->run('notices.notice', ['notice_text' => \__('Please clear your cache to ensure tracking works properly.', 'independent-analytics'), 'button_text' => \__('I\'ve cleared the cache', 'independent-analytics'), 'id' => 'iawp_need_clear_cache', 'notice' => 'iawp-warning', 'url' => 'https://independentwp.com/knowledgebase/common-questions/views-not-recording/']);
            }
            $requires_logged_in_tracking = $plugin_conflict_detector->plugin_requiring_logged_in_tracking();
            $show_logged_in_tracking_notice = $requires_logged_in_tracking && !\get_option('iawp_track_authenticated_users') && !\get_option('iawp_need_clear_cache') && !\get_option('iawp_logged_in_tracking_notice');
            if ($show_logged_in_tracking_notice) {
                echo \IAWPSCOPED\iawp_blade()->run('notices.notice', ['notice_text' => '<strong>' . \sprintf(\_x('%s compatibility:', 'Variable is the name of a plugin', 'independent-analytics'), $requires_logged_in_tracking) . '</strong> ' . \__('We recommend you enable tracking for logged-in visitors.', 'independent-analytics'), 'button_text' => \__('Dismiss', 'independent-analytics'), 'id' => 'enable-logged-in-tracking', 'notice' => 'iawp-warning', 'url' => 'https://independentwp.com/knowledgebase/tracking/how-to-track-logged-in-visitors/']);
            }
            if (\IAWPSCOPED\iawp_db_version() > 0 && !Database::has_correct_database_privileges()) {
                echo \IAWPSCOPED\iawp_blade()->run('notices.notice', ['notice_text' => \__('Your site is missing the following critical database permissions:', 'independent-analytics') . ' ' . \implode(', ', Database::missing_database_privileges()) . '. ' . \__('There is no issue at this time, but you will need to enable the missing permissions before updating the plugin to a newer version to ensure an error is avoided. Please click this link to read our tutorial:', 'independent-analytics'), 'button_text' => \false, 'id' => 'missing-permissions', 'notice' => 'iawp-error', 'url' => 'https://independentwp.com/knowledgebase/common-questions/missing-database-permissions/']);
            }
            if (Env::get_tab() === 'clicks') {
                if (!\get_option('iawp_clicks_sync_notice')) {
                    echo \IAWPSCOPED\iawp_blade()->run('notices.notice', ['notice_text' => \__('Click data syncs every 60 seconds. Please allow for this delay when testing clicks on new links.', 'independent-analytics'), 'button_text' => \__('Dismiss', 'independent-analytics'), 'id' => 'iawp_clicks_sync_notice', 'notice' => 'iawp-warning', 'url' => 'https://independentwp.com/knowledgebase/click-tracking/click-tracking-update-process/']);
                }
            }
        }
        ?>
        </div><?php 
        if (\get_option('iawp_show_gsg') === '1' && !\get_option('iawp_need_clear_cache') && !$show_logged_in_tracking_notice && !$plugin_conflict_detector->has_conflict() && (Env::get_tab() !== 'clicks' || Env::get_tab() === 'clicks' && \get_option('iawp_clicks_sync_notice'))) {
            echo \IAWPSCOPED\iawp_blade()->run('notices.getting-started');
        }
    }
}
