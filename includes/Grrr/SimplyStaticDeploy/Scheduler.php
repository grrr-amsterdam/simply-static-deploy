<?php namespace Grrr\SimplyStaticDeploy;

use WP_Error;
use DateTime;
use DateTimeZone;

/**
 * Add scheduled generation and deploy of static site.
 */
class Scheduler {

    const START_TIME = '12:00:00'; // Half an hour after daily imports

    const INTERVAL = 'daily';

    const EVENT = 'grrr_static_site_deploy_event';

    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function register() {
        if (!wp_next_scheduled(self::EVENT)) {
            $datetime = new DateTime(
                self::START_TIME,
                new DateTimeZone(get_option('timezone_string'))
            );
            wp_schedule_event($datetime->getTimestamp(), self::INTERVAL, self::EVENT);
        }

        add_action(self::EVENT, [$this, 'deploy']);
    }

    public function deploy() {
        $generator = new Generator();
        $generate = $generator->generate();
        if ($generate instanceof WP_Error) {
            return;
        }

        $syncer = new Syncer($this->config);
        $sync = $syncer->sync(Archive::get_directory());
        if ($sync instanceof WP_Error) {
            return;
        }

        $invalidator = new Invalidator($this->config);
        $invalidate = $invalidator->invalidate();
    }

    public function clear() {
        wp_clear_scheduled_hook(self::EVENT);
    }

}
