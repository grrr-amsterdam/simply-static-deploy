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

- [grrr_simply_static_deploy_error](#grrr_simply_static_deploy_error)
- [grrr_simply_static_deploy_modify_generated_files](#grrr_simply_static_deploy_modify_generated_files)
- [grrr_simply_static_deploy_schedule](#grrr_simply_static_deploy_schedule)

#### grrr_simply_static_deploy_error

Called from the plugin, and receives a `WP_Error` object explaining the error. You can decide how to handle the error, for instance by logging it with a service of choice.

```php
add_action('grrr_simply_static_deploy_error', function (\WP_Error $error) {
    # Handle the error.
});
```

#### grrr_simply_static_deploy_modify_generated_files

Called when Simply Static is done generating the static site. This allows you to modify the generated files before they're being deployed. The static site directory is passed as an argument.

```php
add_action('grrr_simply_static_deploy_modify_generated_files', function (string $directory) {
    # Modify generated files, like renaming or moving them.
});

#### grrr_simply_static_deploy_schedule

Schedule a deploy event. 

Arguments:

- **Time**: should be a simple time string, it is automatically converted to a UNIX timestamp in the configured WordPress timezone.
- **Interval**: accepted values are `hourly`, `twicedaily` and `daily`. Can be extended via [cron_schedules](https://developer.wordpress.org/reference/hooks/cron_schedules).

```php
do_action('grrr_simply_static_deploy_schedule', '12:00', 'daily');
```

Note: it is important that [WP-Cron](https://developer.wordpress.org/plugins/cron/) is called regularly. You could do so by disabling the default WP-Cron mechanism and switch to calling it via a dedicated [cronjob](https://en.wikipedia.org/wiki/Cronjob).

To disable the default WP–Cron (which is normally called when a user visits pages), add the following to your WordPress configuration:
 
```php
define('DISABLE_WP_CRON', true);
```

Create a cronjob calling the WordPres WP-Cron. Setting it to _every 5 minutes_ would be a good default. For example via  `crontab -e` on a Linux machine:

```cron
*/5 * * * * curl https://example.com/wp/wp-cron.php?doing-cron > /dev/null 2>&1
```
