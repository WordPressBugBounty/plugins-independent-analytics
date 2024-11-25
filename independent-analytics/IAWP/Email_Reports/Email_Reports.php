<?php

namespace IAWP\Email_Reports;

use DateTime;
use IAWP\Email_Reports\Intervals\Monthly;
use IAWP\Rows\Campaigns;
use IAWP\Rows\Countries;
use IAWP\Rows\Device_Types;
use IAWP\Rows\Pages;
use IAWP\Rows\Referrers;
use IAWP\Sort_Configuration;
use IAWP\Statistics\Page_Statistics;
use IAWP\Statistics\Statistic;
use IAWP\Utils\URL;
use IAWP\Utils\Timezone;
/** @internal */
class Email_Reports
{
    public function __construct()
    {
        $monitored_options = ['iawp_email_report_interval', 'iawp_email_report_time', 'iawp_email_report_email_addresses'];
        foreach ($monitored_options as $option) {
            \add_action('update_option_' . $option, [$this, 'schedule'], 10, 0);
            \add_action('add_option_' . $option, [$this, 'schedule'], 10, 0);
        }
        // Maybe reschedule when starting day of the week is changed
        \add_action('update_option_iawp_dow', [$this, 'maybe_reschedule'], 10, 0);
        \add_action('add_option_iawp_dow', [$this, 'maybe_reschedule'], 10, 0);
        \add_action('iawp_send_email_report', [$this, 'send_email_report']);
    }
    private function interval() : \IAWP\Email_Reports\Interval
    {
        return \IAWP\Email_Reports\Interval_Factory::from_option();
    }
    public function schedule()
    {
        $this->unschedule();
        if (empty(\IAWPSCOPED\iawp()->get_option('iawp_email_report_email_addresses', []))) {
            return;
        }
        \wp_schedule_event($this->interval()->next_interval_start()->getTimestamp(), $this->interval()->id(), 'iawp_send_email_report');
    }
    public function unschedule()
    {
        $timestamp = \wp_next_scheduled('iawp_send_email_report');
        if (\is_int($timestamp)) {
            \wp_unschedule_event($timestamp, 'iawp_send_email_report');
        }
    }
    /**
     * For testing purposes, get the
     *
     * @return DateTime
     */
    public function next_event_scheduled_at() : ?DateTime
    {
        if (!\wp_next_scheduled('iawp_send_email_report')) {
            return null;
        }
        $date = new DateTime();
        $date->setTimezone(Timezone::site_timezone());
        $date->setTimestamp(\wp_next_scheduled('iawp_send_email_report'));
        return $date;
    }
    public function next_email_at_for_humans() : string
    {
        if (!\wp_next_scheduled('iawp_send_email_report')) {
            return \esc_html__('There is no email scheduled.', 'independent-analytics');
        }
        $date = $this->interval()->next_interval_start();
        $day = $date->format(\get_option('date_format'));
        $time = $date->format(\IAWPSCOPED\iawp()->get_option('time_format', 'g:i a'));
        return \sprintf(\__('Next email scheduled for %s at %s.', 'independent-analytics'), '<span>' . $day . '</span>', '<span>' . $time . '</span>');
    }
    public function maybe_reschedule()
    {
        if (!\wp_next_scheduled('iawp_send_email_report')) {
            return;
        }
        if (\IAWPSCOPED\iawp()->get_option('iawp_email_report_interval', 'monthly') != 'weekly') {
            return;
        }
        $this->schedule();
    }
    public function send_email_report(bool $is_test_email = \false)
    {
        // This code was added to fix an issue with monthly email reports. When you set up a monthly
        // email report, the first email will always be correct because we pick the exact timestamp
        // we want to send it. The issue is with the recurring interval. It's backed by MONTH_IN_SECONDS
        // which is a fixed constant. Months have a varying number of seconds so this caused slight
        // differences in when subsequent monthly email reports were sent. The code below fixes this by
        // rescheduling monthly email reports as they're sent. That allows us to pinpoint the exact
        // correct next time to run it, while still falling back to the inaccurate monthly interval
        // should a given email never send for some reason.
        if ($this->interval()->id() === 'monthly') {
            $this->schedule();
        }
        $to = \IAWPSCOPED\iawp()->get_option('iawp_email_report_email_addresses', []);
        if (empty($to)) {
            return;
        }
        $from = \IAWPSCOPED\iawp()->get_option('iawp_email_report_from_address', \get_option('admin_email'));
        $body = $this->get_email_body();
        $headers[] = 'From: ' . \get_bloginfo('name') . ' <' . \esc_attr($from) . '>';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        return \wp_mail($to, $this->subject_line($is_test_email), $body, $headers);
    }
    public function get_email_body($colors = '')
    {
        $statistics = new Page_Statistics($this->interval()->date_range());
        $quick_stats = \array_values(\array_filter($statistics->get_statistics(), function (Statistic $statistics) {
            return $statistics->is_visible() && $statistics->is_group_plugin_enabled();
        }));
        $chart = new \IAWP\Email_Reports\Email_Chart($statistics);
        $colors = $colors == '' ? \IAWPSCOPED\iawp()->get_option('iawp_email_report_colors', ['#5123a0', '#fafafa', '#3a1e6b', '#fafafa', '#5123a0', '#a985e6', '#ece9f2', '#f7f5fa', '#ece9f2', '#dedae6']) : \explode(',', $colors);
        return \IAWPSCOPED\iawp_blade()->run('email.email', [
            'site_title' => \get_bloginfo('name'),
            'site_url' => URL::new(\get_site_url())->get_domain(),
            'date' => $this->interval()->report_time_period_for_humans(),
            // The value that needs to change
            'stats' => $quick_stats,
            'top_ten' => $this->get_top_ten(),
            'chart_views' => $chart->views,
            'chart_title' => $this->interval()->chart_title(),
            'most_views' => $chart->most_views,
            'y_labels' => $chart->y_labels,
            'x_labels' => $chart->x_labels,
            'colors' => $colors,
        ]);
    }
    private function subject_line(bool $is_test_email) : string
    {
        $parts = [];
        if ($is_test_email) {
            $parts[] = \__('[Test]', 'independent-analytics');
        }
        $parts[] = \__('Analytics Report for', 'independent-analytics');
        $parts[] = \get_bloginfo('name');
        $parts[] = '[' . $this->interval()->report_time_period_for_humans() . ']';
        return \esc_html(\implode(' ', $parts));
    }
    private function get_top_ten() : array
    {
        $date_range = $this->interval()->date_range();
        $queries = ['pages' => 'title', 'referrers' => 'referrer', 'countries' => 'country', 'devices' => 'device_type', 'campaigns' => 'title', 'landing_pages' => 'title', 'exit_pages' => 'title'];
        $top_ten = [];
        $sort_configuration = new Sort_Configuration('views', 'desc');
        $title = '';
        foreach ($queries as $type => $title) {
            if ($type === 'pages') {
                $query = new Pages($date_range, 10, null, $sort_configuration);
                $title = \esc_html__('Pages', 'independent-analytics');
            } elseif ($type === 'referrers') {
                $query = new Referrers($date_range, 10, null, $sort_configuration);
                $title = \esc_html__('Referrers', 'independent-analytics');
            } elseif ($type === 'countries') {
                $query = new Countries($date_range, 10, null, $sort_configuration);
                $title = \esc_html__('Countries', 'independent-analytics');
            } elseif ($type === 'devices') {
                $query = new Device_Types($date_range, 10, null, $sort_configuration);
                $title = \esc_html__('Devices', 'independent-analytics');
            } elseif ($type === 'campaigns') {
                $query = new Campaigns($date_range, 10, null, $sort_configuration);
                $title = \esc_html__('Campaigns', 'independent-analytics');
            } elseif ($type === 'landing_pages') {
                $query = new Pages($date_range, 10, null, new Sort_Configuration('entrances', 'desc'));
                $title = \esc_html__('Landing Pages', 'independent-analytics');
            } elseif ($type === 'exit_pages') {
                $query = new Pages($date_range, 10, null, new Sort_Configuration('exits', 'desc'));
                $title = \esc_html__('Exit Pages', 'independent-analytics');
            } else {
                continue;
            }
            $rows = \array_map(function ($row, $index) use($type) {
                if ($type == 'referrers') {
                    $edited_title = $row->referrer();
                } elseif ($type == 'countries') {
                    $edited_title = $row->country();
                } elseif ($type == 'devices') {
                    $edited_title = $row->device_type();
                } elseif ($type == 'campaigns') {
                    $edited_title = $row->utm_campaign();
                } else {
                    $edited_title = $row->title();
                }
                $edited_title = \mb_strlen($edited_title) > 30 ? \mb_substr($edited_title, 0, 30) . '...' : $edited_title;
                $metric = 'views';
                if ($type == 'landing_pages') {
                    $metric = 'entrances';
                } elseif ($type == 'exit_pages') {
                    $metric = 'exits';
                }
                return ['title' => $edited_title, 'views' => $row->{$metric}()];
            }, $query->rows(), \array_keys($query->rows()));
            if (\count($rows) == 0) {
                continue;
            }
            $top_ten[$type] = ['title' => $title, 'rows' => $rows];
        }
        return $top_ten;
    }
}
