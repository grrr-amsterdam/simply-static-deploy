<?php namespace Grrr\SimplyStaticDeploy\Aws;

use Aws\Sdk;
use Aws\S3\S3Client;
use Aws\CloudFront\CloudFrontClient;
use Garp\Functional as f;

class ClientProvider {

    protected $_sdk;

    public function __construct(array $config) {
        $key = f\prop('key', $config);
        $secret = f\prop('secret', $config);

        $credentials = $key && $secret
            ? (new CredentialsProvider($key, $secret))->getCredentials()
            : null;

        $this->_sdk = new Sdk([
            'credentials' => $credentials,
            'region' => f\prop('region', $config),
            'version' => 'latest',
            'http' => [
                'timeout' => 30,
            ],
        ]);
    }

    public function getS3Client(): S3Client {
        return $this->_sdk->createS3();
    }

    public function getCloudFrontClient(): CloudFrontClient {
        return $this->_sdk->createCloudFront();
    }

}

