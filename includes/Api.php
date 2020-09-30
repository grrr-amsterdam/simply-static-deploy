<?php

namespace Grrr\SimplyStaticDeploy;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Api
{
    const ENDPOINT_MAPPER = [
        'generate' => 'generate_bundle',
        'generate_single' => 'generate_single',
        'sync' => 'sync_to_s3',
        'invalidate' => 'invalidate_cloudfront',
    ];

    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function register()
    {
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
    }

    public function register_api_endpoints()
    {
        foreach (self::ENDPOINT_MAPPER as $endpoint => $callback) {
            register_rest_route(
                RestRoutes::NAMESPACE,
                RestRoutes::get($endpoint),
                [
                    'methods' => 'POST',
                    'callback' => [$this, $callback],
                    'permission_callback' => function () {
                        return true;
                        // return current_user_can('edit_posts');
                    },
                ]
            );
        }
    }

    /**
     * Static site generation endpoint callback, invoking Simply Static tasks.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function generate_bundle(WP_REST_Request $request)
    {
        $response = (new Generator())->generate();
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response('Bundle generated.', 200);
    }

    public function generate_single(WP_REST_Request $request)
    {
        $post_id = $request->get_param('id');
        $response = (new Generator())->generate($post_id);
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response(
                sprintf('Single page %d generated.', $post_id),
                200
            );
    }

    /**
     * S3 endpoint callback.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function sync_to_s3(WP_REST_Request $request)
    {
        $path = Archiver::get_directory();
        $syncer = new Syncer($this->config->aws);
        $response = $syncer->sync($path);
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response('Synced to S3.', 200);
    }

    /**
     * CloudFront endpoint callback.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function invalidate_cloudfront(WP_REST_Request $request)
    {
        $invalidator = new Invalidator($this->config->aws);
        $response = $invalidator->invalidate();
        return $response instanceof WP_Error
            ? $response
            : new WP_REST_Response('CloudFront invalidated.', 200);
    }
}
