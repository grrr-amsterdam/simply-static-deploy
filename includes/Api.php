<?php

namespace Grrr\SimplyStaticDeploy;

use DateTime;
use DateTimeZone;
use Garp\Functional as f;
use Simply_Static\Util;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Api {

    const SCHEDULE_ACTION = 'simply_static_deploy_schedule';
    const EVENT_BASE = 'simply_static_deploy_event';

    const ENDPOINT_MAPPER = [
        'generate_single' => 'generate_single',
        'simply_static_deploy' => 'simply_static_deploy',
        'poll_status' => 'poll_status',
    ];

    private $config;

    protected $staticDeployJob;

    public function __construct(Config $config) {
        $this->config = $config;
        $this->staticDeployJob = new StaticDeployJob;
    }

    public function register() {
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
        add_action(static::SCHEDULE_ACTION, [$this, 'schedule'], 10, 3);
    }

    public function register_api_endpoints() {
        foreach (self::ENDPOINT_MAPPER as $endpoint => $callback) {
            register_rest_route(
                RestRoutes::NAMESPACE,
                RestRoutes::get($endpoint),
                [
                    'methods' => 'POST',
                    'callback' => [$this, $callback],
                    'permission_callback' => function () {
                        return current_user_can('edit_posts');
                    },
                ]
            );
        }
    }

    public function schedule(string $time, string $interval): void
    {
        $datetime = new DateTime(
            $time,
            new DateTimeZone(get_option('timezone_string'))
        );
        $event = $this->generate_event_name($datetime, $interval);
        if (!wp_next_scheduled($event)) {
            wp_schedule_event($datetime->getTimestamp(), $interval, $event);
        }

        add_action($event, [$this, 'deploy']);
    }

    public function deploy(): void
    {
        $this->staticDeployJob->start();
    }

    private function generate_event_name(
        DateTime $datetime,
        string $interval
    ): string {
        return f\join('_', [
            static::EVENT_BASE,
            $interval,
            $datetime->format('H'),
            $datetime->format('i'),
        ]);
    }

    public function simply_static_deploy(WP_REST_Request $request) {
        Util::delete_debug_log();
        Util::debug_log("Received request to start generating a static archive");
        $response = $this->staticDeployJob->start();
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response('Deployment in progress...', 200);
    }

    public function generate_single(WP_REST_Request $request) {
        $post_id = $request->get_param('post_id');
        Util::delete_debug_log();
        Util::debug_log("Received request to start static deploy for postId: " . $post_id);
        $response = $this->staticDeployJob->start($post_id);
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response('Deploying single', 200);
    }

    public function poll_status(WP_REST_Request $request) {
        $status = StaticDeployJob::is_job_done() ? 'ready' : 'busy';
        return new WP_REST_Response($status, 200);
    }
}
