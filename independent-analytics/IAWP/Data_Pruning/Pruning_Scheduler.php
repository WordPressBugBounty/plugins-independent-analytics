<?php

namespace IAWP\Data_Pruning;

use IAWPSCOPED\Carbon\CarbonImmutable;
use IAWP\Illuminate_Builder;
use IAWP\Query;
use IAWP\Utils\Format;
use IAWP\Utils\Timezone;
/** @internal */
class Pruning_Scheduler
{
    public static $cutoff_options = [['disabled', 'Keep data forever'], ['thirty-days', 'Keep data for 30 days'], ['sixty-days', 'Keep data for 60 days'], ['ninety-days', 'Keep data for 90 days'], ['one-hundred-and-eighty-days', 'Keep data for 180 days'], ['one-year', 'Keep data for 1 year'], ['two-years', 'Keep data for 2 years'], ['three-years', 'Keep data for 3 years'], ['four-years', 'Keep data for 4 years']];
    public function cutoff_options() : array
    {
        return self::$cutoff_options;
    }
    public function is_enabled() : bool
    {
        $value = \get_option('iawp_pruning_cutoff');
        if (!\is_string($value)) {
            return \false;
        }
        if ($this->convert_cutoff_to_date($value) === null) {
            return \false;
        }
        return \true;
    }
    public function get_pruning_cutoff() : string
    {
        return \get_option('iawp_pruning_cutoff', 'disabled');
    }
    public function get_pruning_cutoff_as_datetime() : ?\DateTime
    {
        if (!$this->is_enabled()) {
            return null;
        }
        return $this->convert_cutoff_to_date($this->get_pruning_cutoff());
    }
    public function status_message() : ?string
    {
        if (!$this->is_enabled()) {
            return null;
        }
        $scheduled_at = new \DateTime('now', Timezone::utc_timezone());
        $scheduled_at->setTimezone(Timezone::site_timezone());
        $scheduled_at->setTimestamp(\wp_next_scheduled('iawp_prune'));
        $day = $scheduled_at->format(Format::date());
        $time = $scheduled_at->format(Format::time());
        return \sprintf(\__('Next data pruning scheduled for %s at %s.', 'independent-analytics'), '<strong>' . $day . '</strong>', '<strong>' . $time . '</strong>');
    }
    public function get_confirmation_message(string $cutoff) : string
    {
        $utc_date = $this->convert_cutoff_to_date($cutoff);
        if ($utc_date === null) {
            return '';
        }
        $date = $this->convert_cutoff_to_date($cutoff)->setTimezone(Timezone::site_timezone());
        $formatted_date = $date->format(Format::date());
        $estimates = $this->get_pruning_estimates($utc_date);
        return \sprintf(\__("All data from before %1\$s will be deleted immediately. This will remove %2\$s of your %3\$s tracked sessions. \n\n This process will repeat daily at midnight.", 'independent-analytics'), $formatted_date, \number_format_i18n($estimates['sessions_to_be_deleted']), \number_format_i18n($estimates['sessions']));
    }
    /**
     * @return array{sessions: int, sessions_to_be_deleted: int}
     */
    public function get_pruning_estimates(\DateTime $cutoff_date) : array
    {
        $sessions_table = Query::get_table_name(Query::SESSIONS);
        $sessions = Illuminate_Builder::new()->from($sessions_table)->selectRaw('COUNT(*) as sessions')->value('sessions');
        $sessions_to_be_deleted = Illuminate_Builder::new()->from($sessions_table)->selectRaw('COUNT(*) as sessions')->where('created_at', '<', $cutoff_date)->value('sessions');
        return ['sessions' => $sessions, 'sessions_to_be_deleted' => $sessions_to_be_deleted];
    }
    public function update_pruning_cutoff(string $cutoff) : bool
    {
        $is_valid_cutoff = \false;
        foreach (self::$cutoff_options as $option) {
            if ($option[0] === $cutoff) {
                $is_valid_cutoff = \true;
            }
        }
        if (!$is_valid_cutoff) {
            return \false;
        }
        \update_option('iawp_pruning_cutoff', $cutoff, \true);
        $this->schedule_pruning($cutoff);
        return \true;
    }
    /**
     * Attempt to schedule pruning based on whatever existing cutoff option is saved
     *
     * @return void
     */
    public function schedule() : void
    {
        $cutoff = \get_option('iawp_pruning_cutoff', '');
        $this->update_pruning_cutoff($cutoff);
    }
    private function schedule_pruning(string $cutoff) : void
    {
        \wp_unschedule_hook('iawp_prune');
        if ($cutoff === 'disabled') {
            return;
        }
        $tomorrow = new \DateTime('tomorrow', Timezone::site_timezone());
        \wp_schedule_event($tomorrow->getTimestamp(), 'daily', 'iawp_prune');
    }
    private function convert_cutoff_to_date(string $cutoff) : ?\DateTime
    {
        $date = new CarbonImmutable('today', Timezone::site_timezone());
        switch ($cutoff) {
            case 'thirty-days':
                $date = $date->subDays(30);
                break;
            case 'sixty-days':
                $date = $date->subDays(60);
                break;
            case 'ninety-days':
                $date = $date->subDays(90);
                break;
            case 'one-hundred-and-eighty-days':
                $date = $date->subDays(180);
                break;
            case 'one-year':
                $date = $date->subYearsNoOverflow(1);
                break;
            case 'two-years':
                $date = $date->subYearsNoOverflow(2);
                break;
            case 'three-years':
                $date = $date->subYearsNoOverflow(3);
                break;
            case 'four-years':
                $date = $date->subYearsNoOverflow(4);
                break;
            default:
                return null;
        }
        return $date->setTimezone(Timezone::utc_timezone())->toDate();
    }
}
