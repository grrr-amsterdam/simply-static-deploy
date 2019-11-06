<?php namespace Grrr\SimplyStaticDeploy;

use Grrr\Aws;
use Grrr\Aws\CloudFront;
use Garp\Functional as f;

class Invalidator {

    const OPTION_TIMESTAMP_KEY = 'grrr_static_site_invalidated_at';

    /**
     * Invalidate full CloudFront distribution.
     *
     * @return WP_Error|bool
     */
    public function invalidate() {
        $clientProvider = new Aws\ClientProvider(AWS_SITE);
        $invalidation = new CloudFront\Invalidation(
            $clientProvider->getCloudFrontClient(),
            f\prop('distribution', AWS_SITE)
        );
        $result = $invalidation->invalidate(['/*']);

        if (!$result instanceof \WP_Error) {
            update_option(self::OPTION_TIMESTAMP_KEY, time());
        }

        return $result;
    }

    /**
     * Return the last invalidation time.
     *
     * @return string
     */
    public static function get_last_time(): string {
        return get_option(self::OPTION_TIMESTAMP_KEY) ?: '';
    }

}
