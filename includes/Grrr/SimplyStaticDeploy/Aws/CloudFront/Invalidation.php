<?php namespace Grrr\SimplyStaticDeploy\Aws\CloudFront;

use WP_Error;
use Exception;
use Aws\Exception\AwsException;
use Aws\CloudFront\CloudFrontClient;
use Aws\CloudFront\Exception\CloudFrontException;

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
        } catch (AwsException $error) {
            $message = $error->getAwsRequestId() . PHP_EOL;
            $message .= $error->getAwsErrorType() . PHP_EOL;
            $message .= $error->getAwsErrorCode() . PHP_EOL;
        } catch (CloudFrontException | Exception $error) {
            $message = $error->getMessage();
        }

        $error = new WP_Error('cloudfront_invalidation_error', sprintf( __("Could not invalidate CloudFront distribution: %s", 'grrr'), $message), [
            'status' => 400,
        ]);
        do_action('grrr_simply_static_deploy_error', $error);
        return $error;
    }
}
