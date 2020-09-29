<?php namespace Grrr\SimplyStaticDeploy\Aws;

use GuzzleHttp\Promise;
use Aws\Credentials\Credentials;

class CredentialsProvider
{
    protected $key;
    protected $secret;

    public function __construct(string $key, string $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    public function getCredentials(): callable
    {
        return function () {
            return Promise\promise_for(
                new Credentials($this->key, $this->secret)
            );
        };
    }
}
