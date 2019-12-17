<?php namespace Grrr\SimplyStaticDeploy\Aws\S3;

use WP_Error;
use Aws\Command;
use Aws\S3\S3Client;
use Aws\S3\Transfer;
use Aws\S3\Exception\S3Exception;
use Aws\Exception\AwsException;
use Garp\Functional as f;

class TransferManager {

    const TTL = [
        'docs' => 300, // 5 minutes
        'assets' => 2592000, // 1 month
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

    protected $client;
    protected $manager;

    public function __construct(S3Client $client, string $bucket, string $acl, $source) {
        $this->_manager = new Transfer($client, $source, 's3://' . $bucket, [
            'concurrency' => 5,
            'before' => function(Command $command) use ($acl) {
                $filepath = strtolower(f\prop('Key', $command));
                $extension = pathinfo($filepath, PATHINFO_EXTENSION) ?: '';

                // Set canned ACL. Usually this would be `public`, but restricted
                // setups might require `private` or `aws-exec-read`.
                $command['ACL'] = $acl;

                // Set MIME type for extension that are not properly recognized.
                $mime = $this->get_mime_type($filepath, $extension);
                if ($mime) {
                    $command['ContentType'] = $mime;
                }

                // Set `Cache-Control` header with `max-age`.
                $command['CacheControl'] = 'public, max-age=' . $this->get_ttl($extension);

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

        $error = new WP_Error('cannot_sync_to_s3', sprintf( __("Could not sync file to S3: %s", 'simply_static_deploy'), $message), [
            'status' => 400,
        ]);
        do_action('simply_static_deploy_error', $error);
        return $error;
    }

    /**
     * Get cache TTL based on file extension.
     */
    protected function get_ttl(string $extension): int {
        return f\contains($extension, static::DOC_EXTENSTIONS)
            ? f\prop('docs', static::TTL)
            : f\prop('assets', static::TTL);
    }

    /**
     * Get MIME type based on file extension.
     */
    protected function get_mime_type(string $filepath, string $extension): string {
        if (strpos($filepath, 'feed/atom/') === 0) {
            return 'application/atom+xml';
        }
        if (strpos($filepath, 'feed/') === 0) {
            return 'application/rss+xml';
        }
        return f\prop($extension, static::MIME_MAPPER) ?? '';
    }

}
