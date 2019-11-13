<?php namespace Grrr\SimplyStaticDeploy\Aws\S3;

use WP_Error;
use Aws\Command;
use Aws\S3\S3Client;
use Aws\S3\Transfer;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;
use Garp\Functional as f;

class TransferManager {

    const DOCS_TTL = [
        'all' => 300,       // 5 minutes
        'cdn' => 300,       // 5 minutes
    ];

    const ASSETS_TTL = [
        'all' => 2592000,   // 1 month
        'cdn' => 2592000,   // 1 month
    ];

    const DOC_EXTENSTIONS = [
        'html',
        'xml',
    ];

    const MIME_MAPPER = [
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'mp4'   => 'video/mp4',
        'webm'  => 'video/webm',
        'xsl'   => 'text/xml',
    ];

    protected $_client;
    protected $_manager;

    public function __construct(S3Client $client, string $bucket, string $acl, $source) {
        $this->_manager = new Transfer($client, $source, 's3://' . $bucket, [
            'concurrency' => 5,
            'before' => function(Command $command) use ($acl) {
                $filepath = strtolower(f\prop('Key', $command));
                $extension = pathinfo($filepath, PATHINFO_EXTENSION) ?: '';

                // Set canned ACL. This must be `private` for the `staging` and
                // `production` buckets, since only CloudFront is allowed access.
                $command['ACL'] = $acl;

                // Set MIME type for extension that are not properly recognized.
                $mime = $this->_get_mime_type($filepath, $extension);
                if ($mime) {
                    $command['ContentType'] = $mime;
                }

                // Set `max-age` and `s-maxage`. The first is used for browser cache,
                // the latter is used to set the CloudFront Edge Cache.
                // Note that it seems if we set the `s-maxage` longer than the `max-age`,
                // the browser will get 'confused'. CloudFront will cache the page
                // or asset for the `s-maxage`, but CloudFront will use the `Age/Date`
                // from the origin. This tells the browser that the asset is actually
                // expired, and therefore the 'kind of valid' `max-age` is discarded.
                // This means the browser will always try to fetch the 'new' assets,
                // resulting in 304's.
                //
                // See: https://www.cdnplanet.com/blog/cloudfront-cachability-date-header/
                // See: https://support.fastly.com/hc/en-us/community/posts/360040446672-Cache-Control-max-age-and-Age-headers
                $ttl = $this->_get_ttl($extension);
                $command['CacheControl'] = 'public, '
                    . 'max-age=' . f\prop('all', $ttl) . ', '
                    . 's-maxage=' . f\prop('cdn', $ttl);

                // @TODO Properly investigate why we actually need this.
                // See: https://github.com/aws/aws-sdk-php/issues/749
                gc_collect_cycles();
            },
        ]);
    }

    /**
     * Start the transfer.
     *
     * @return WP_Error|bool
     */
    public function transfer() {
        try {
            $this->_manager->transfer();
            return true;
        } catch (AwsException $error) {
            $message = $error->getAwsRequestId() . PHP_EOL;
            $message .= $error->getAwsErrorType() . PHP_EOL;
            $message .= $error->getAwsErrorCode() . PHP_EOL;
        } catch (S3Exception | Exception $error) {
            $message = $error->getMessage();
        }

        $error = new WP_Error('cannot_sync_to_s3', sprintf( __("Could not sync file to S3: %s", 'grrr'), $message), [
            'status' => 400,
        ]);
        do_action('grrr_simply_static_deploy_error', $error);
        return $error;
    }

    protected function _get_ttl(string $extension): array {
        return f\contains($extension, static::DOC_EXTENSTIONS)
            ? static::DOCS_TTL
            : static::ASSETS_TTL;
    }

    protected function _get_mime_type(string $filepath, string $extension): string {
        if (strpos($filepath, 'feed/atom/') === 0) {
            return 'application/atom+xml';
        }
        if (strpos($filepath, 'feed/') === 0) {
            return 'application/rss+xml';
        }
        return f\prop($extension, static::MIME_MAPPER) ?? '';
    }

}

