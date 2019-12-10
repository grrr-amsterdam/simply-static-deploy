<?php namespace Grrr\SimplyStaticDeploy;

use Garp\Functional as f;

class Config {

    const REQUIRED_FIELDS = [
        'aws' => [
            'key',
            'secret',
            'region',
            'bucket',
        ],
        'url',
    ];

    const OPTIONAL_FIELDS = [
        'aws' => [
            'bucket_acl',
            'distribution',
        ],
    ];

    private $data;

    public function __construct(array $data) {
        $this->validate($data);
        $this->data = $data;
    }

    public function __get(string $key): string {
        return $this->data[$key] ?? '';
    }

    private function validate(array $data): void {
        $missingProps = f\reject(
            f\prop_of($data),
            static::REQUIRED_FIELDS
        );
        if (count($missingProps)) {
            throw new Exception(
                sprintf('Missing required option(s): %s', implode(', ', $missingProps))
            );
        }
    }

}
