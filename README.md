# Simply Static Deploy

[![Build Status](https://travis-ci.com/grrr-amsterdam/simply-static-deploy.svg?branch=master)](https://travis-ci.com/grrr-amsterdam/simply-static-deploy)

### Deploy static sites easily to an AWS S3 bucket

- Utilizes the excellent [Simply Static](https://wordpress.org/plugins/simply-static/) plugin.
- Adds simple site generation, deployment and invalidation steps.
- Customizable using hooks and actions.

Built with ❤️ by [GRRR](https://grrr.tech).

## Installation

Install via Composer:

```sh
$ composer require grrr-amsterdam/simply-static-deploy
```

This will install it in the plugin directory, assuming you have the right installer path configured in your `composer.json`:

```json
"extra": {
  "installer-paths": {
    "web/app/plugins/{$name}/": ["type:wordpress-plugin"]
  }
}
```

## Configuration

Define this...

```php
define('SIMPLY_STATIC_DEPLOY_AWS_CREDENTIALS', [
    'key'           => env('AWS_SITE_ACCESS_KEY_ID'),
    'secret'        => env('AWS_SITE_SECRET_ACCESS_KEY'),
    'region'        => env('AWS_SITE_REGION'),
    'bucket'        => env('AWS_SITE_S3_BUCKET'),
    'bucket_acl'    => env('AWS_SITE_S3_BUCKET_ACL'), // optional
    'distribution'  => env('AWS_SITE_CF_DISTRIBUTION_ID'), // optional
    'url'           => env('AWS_SITE_WEBSITE_URL'),
]);
```

## Usage

...

## Documentation

[View the documentation](https://github.com/grrr-amsterdam/simply-static-deploy/tree/master/docs) for ...

### Available filters

- [grrr_simply_static_deploy_php_time](#grrr_simply_static_deploy_php_time)

#### grrr_simply_static_deploy_php_time

Adjust the [max_execution_time](https://www.php.net/manual/en/info.configuration.php#ini.max-execution-time) for the static site generation.

```php
add_filter('grrr_simply_static_deploy_php_execution_time', function (int $time) {
    return 600;
});
```

Note: although this will increase the `max_execution_time`, it will not increase the execution time of your webserver. 
For Apache, you might have to increase the [TimeOut Directive](http://httpd.apache.org/docs/2.0/mod/core.html#timeout):

```conf
TimeOut 600
```

### Available actions

- `grrr_simply_static_deploy_error`: Receives a `WP_Error` object explaining the error. You can decide how to handle the error, for instance by logging it with a service of choice.
