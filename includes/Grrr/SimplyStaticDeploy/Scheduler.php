<?php namespace Grrr\SimplyStaticDeploy;

use WP_Error;
use DateTime;
use DateTimeZone;
use Garp\Functional as f;

/**
 * Schedule deploys via WP-Cron.
 *
 * @author Koen Schaft <koen@grrr.nl>
 */
class Scheduler {

    const SCHEDULE_ACTION = 'grrr_simply_static_deploy_schedule';
    const EVENT_BASE = 'grrr_simply_static_deploy_event';

    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function register(): void {
        add_action(static::SCHEDULE_ACTION, [$this, 'schedule'], 10, 3);
    }

    public function schedule(string $time, string $interval): void {
        $datetime = new DateTime($time, new DateTimeZone(get_option('timezone_string')));
        $event = $this->generate_event_name($datetime, $interval);
        if (!wp_next_scheduled($event)) {
            wp_schedule_event($datetime->getTimestamp(), $interval, $event);
        }

        add_action($event, [$this, 'deploy']);
    }

    public function deploy(): void {
        $generator = new Generator();
        $generate = $generator->generate();
        if ($generate instanceof WP_Error) {
            return;
        }

        $syncer = new Syncer($this->config->aws);
        $sync = $syncer->sync(Archive::get_directory());
        if ($sync instanceof WP_Error) {
            return;
        }

        if ($this->config->aws->distribution) {
            $invalidator = new Invalidator($this->config->aws);
            $invalidate = $invalidator->invalidate();
        }
    }

    private function generate_event_name(DateTime $datetime, string $interval): string {
        return f\join('_', [
            static::EVENT_BASE,
            $interval,
            $datetime->format('H'),
            $datetime->format('i'),
        ]);
    }

}
