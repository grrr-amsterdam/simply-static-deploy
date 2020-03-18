<?php namespace Grrr\SimplyStaticDeploy\Aws\CloudFront;

use WP_Error;
use Exception;
use Aws\CloudFront\CloudFrontClient;

class Invalidation {

    protected $region;
    protected $distributionId;

    public function __construct(CloudFrontClient $client, string $distributionId) {
        $this->client = $client;
        $this->distributionId = $distributionId;
    }

    /**
     * Invalidate the distribution.
     *
     * @return WP_Error|bool
     */
    public function invalidate(array $items) {
        try {
            $this->client->createInvalidation([
                'DistributionId' => $this->distributionId,
                'InvalidationBatch' => [
                    'CallerReference' => $this->distributionId . ' ' . time(),
                    'Paths' => [
                        'Items' => $items,
                        'Quantity' => count($items),
                    ],
                ],
            ]);
            return true;
        } catch (Exception $error) {
            $response = new WP_Error('cloudfront_invalidation_error', sprintf( __("Could not invalidate CloudFront distribution: %s", 'simply_static_deploy'), $error->getMessage()), [
                'status' => 400,
            ]);
            do_action('simply_static_deploy_error', $response);
            return $response;
        }
    }
}
