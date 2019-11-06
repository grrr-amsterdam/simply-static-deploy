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
        $generate = (new Generator)->generate();
        if ($generate instanceof WP_Error) {
            return;
        }

        $sync = (new Syncer)->sync(Generator::get_directory());
        if ($sync instanceof WP_Error) {
            return;
        }

        $invalidate = (new Invalidator)->invalidate();
    }

    public function clear() {
        wp_clear_scheduled_hook(self::EVENT);
    }

}
