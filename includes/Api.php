<?php

namespace Grrr\SimplyStaticDeploy;

use Simply_Static\Util;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Api {
    const ENDPOINT_MAPPER = [
        'generate' => 'generate_bundle',
        'generate_single' => 'generate_single',
        'sync' => 'sync_to_s3',
        'invalidate' => 'invalidate_cloudfront',
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
