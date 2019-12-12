<?php namespace Grrr\SimplyStaticDeploy;

use WP_Error;
use FileSystemIterator;
use Garp\Functional as f;

class Syncer {

    const OPTION_TIMESTAMP_KEY = 'grrr_simply_static_deploy_synced_at';

    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * Sync files to S3.
     *
     * @return WP_Error|bool
     */
    public function sync(string $path) {
        if (!file_exists($path) || !(new FilesystemIterator($path))->valid()) {
            return new WP_Error('grrr_simply_static_deploy_syncer', __("No generated site found on {$path}, please start a full 'Generate & Deploy' sequence.", 'simply_static_deploy'), [
                'status' => 400,
            ]);
        }

        $clientProvider = new Aws\ClientProvider($this->config);
        $transferManager = new Aws\S3\TransferManager(
            $clientProvider->getS3Client(),
            $this->config->bucket,
            $this->config->bucket_acl ?: 'public-read',
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
