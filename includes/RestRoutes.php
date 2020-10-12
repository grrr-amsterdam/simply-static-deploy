<?php

namespace Grrr\SimplyStaticDeploy;

use Garp\Functional as f;

class RestRoutes {
    const NAMESPACE = 'grrr/simply-static-deploy/v1';

    const ROUTES = [
        'generate_single' => 'generate_single',
        'simply_static_deploy' => 'simply_static_deploy',
        'poll_status' => 'poll_status',
    ];

    public static function get(string $name): string {
        return f\prop($name, static::ROUTES);
    }

    public static function get_all(bool $full = true): array {
        return f\reduce(
            function ($acc, $route) use ($full) {
                $acc[] = $full
                    ? '/' . static::NAMESPACE . '/' . $route
                    : $route;
                return $acc;
            },
            [],
            static::ROUTES
        );
    }

    public static function url(string $name): string {
        return rest_url(static::NAMESPACE . '/' . static::get($name));
    }
}
