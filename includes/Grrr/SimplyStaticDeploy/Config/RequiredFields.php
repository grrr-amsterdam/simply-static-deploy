<?php namespace Grrr\SimplyStaticDeploy\Config;

use Garp\Functional as f;

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

    public static function get_missing_options(array $data, array $requiredFields = self::REQUIRED_FIELDS, string $prefix = ''): array {
        return f\reduce_assoc(
            function (array $missing, $value, $key) use ($data, $prefix): array {
                if (is_array($value)) {
                    return f\concat($missing, static::get_missing_options($data[$key] ?? [], $value, "{$key}."));
                }
                return !isset($data[$value])
                    ? f\concat($missing, [$prefix . $value])
                    : $missing;
            },
            [],
            $requiredFields
        );
    }

    public static function to_array(): array {
        return static::REQUIRED_FIELDS;
    }

}
