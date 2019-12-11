<?php namespace Grrr\SimplyStaticDeploy\Config;

final class RequiredFields {

    const REQUIRED_FIELDS = [
        'aws' => [
            'key',
            'secret',
            'region',
            'bucket',
        ],
        'url',
    ];

    public static function toArray(): array {
        return static::REQUIRED_FIELDS;
    }

}
