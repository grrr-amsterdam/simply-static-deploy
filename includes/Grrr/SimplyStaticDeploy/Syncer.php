<?php namespace Grrr\SimplyStaticDeploy;

use WP_Error;
use Grrr\Aws;
use Grrr\Aws\S3;
use FileSystemIterator;
use Garp\Functional as f;

class Syncer {

    const OPTION_TIMESTAMP_KEY = 'grrr_static_site_synced_at';

    /**
     * Sync files to S3.
     *
     * @return WP_Error|bool
     */
    public function sync(string $path) {
        if (!file_exists($path) || !(new FilesystemIterator($path))->valid()) {
            return new WP_Error('cannot_sync_to_s3', "No generated site found on {$path}, please start a full 'Generate & Deploy' sequence.", [
                'status' => 400,
            ]);
        }

        $clientProvider = new Aws\ClientProvider(AWS_SITE);
        $transferManager = new S3\TransferManager(
            $clientProvider->getS3Client(),
            f\prop('bucket', AWS_SITE),
            f\prop('bucket_acl', AWS_SITE),
            $path
        );
        $result = $transferManager->transfer();

        if (!$result instanceof WP_Error) {
            update_option(self::OPTION_TIMESTAMP_KEY, time());
        }

        return $result;
    }

    /**
     * Return the last sync time.
     *
     * @return string
     */
    public static function get_last_time(): string {
        return get_option(self::OPTION_TIMESTAMP_KEY) ?: '';
    }

}
