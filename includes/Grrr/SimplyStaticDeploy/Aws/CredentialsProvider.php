<?php namespace Grrr\SimplyStaticDeploy\Aws;

use GuzzleHttp\Promise;
use Aws\Credentials\Credentials;

class CredentialsProvider {

    protected $_key;
    protected $_secret;

    public function __construct(string $key, string $secret) {
        $this->_key = $key;
        $this->_secret = $secret;
    }

    public function getCredentials(): callable {
        return function () {
            return Promise\promise_for(
                new Credentials($this->_key, $this->_secret)
            );
        };
    }

}
