<?php namespace Grrr\SimplyStaticDeploy;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Api {

    const ENPOINT_BASE = 'static-site';

    const ENDPOINT_MAPPER = [
        'generate'            => 'generate_bundle',
        'sync'                => 'sync_to_s3',
        'invalidate'          => 'invalidate_cloudfront',
    ];

    public function register() {
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
    }

    public function register_api_endpoints() {
        foreach (self::ENDPOINT_MAPPER as $endpoint => $callback) {
            register_rest_route('grrr/v1', static::ENPOINT_BASE . '/' . $endpoint, [
                'methods' => 'POST',
                'callback' => [$this, $callback],
                'permission_callback' => function() {
                    return current_user_can('edit_posts');
                },
            ]);
        }
    }

    /**
     * Static site generation endpoint callback, invoking Simply Static tasks.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function generate_bundle(WP_REST_Request $params) {
        $response = (new Generator)->generate();
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response('Bundle generated.', 200);
    }

    /**
     * S3 endpoint callback.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function sync_to_s3(WP_REST_Request $params) {
        $path = Generator::get_directory();
        $response = (new Syncer)->sync($path);
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response('Synced to S3.', 200);
    }

    /**
     * CloudFront endpoint callback.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function invalidate_cloudfront(WP_REST_Request $params) {
        $response = (new Invalidator)->invalidate();
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response('CloudFront invalidated.', 200);
    }

}
