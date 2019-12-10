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

## Usage

First define `SIMPLY_STATIC_DEPLOY_CONFIG` in your WordPress configuration:

```php
define('SIMPLY_STATIC_DEPLOY_CONFIG', [
    'aws' => [
        'key'           => '...', # AWS access key
        'secret'        => '...', # AWS secret key
        'region'        => '...', # AWS region
        'bucket'        => '...', # S3 bucket
        'bucket_acl'    => '...', # S3 bucket ACL (optional, defaults to `public-read`)
        'distribution'  => '...', # CloudFront distribution ID (optional, step is skipped when empty)
    ],
    'url' => '...', # Website url (used for displaying url after deploy is finished)
]);
```

Then configure the Simply Static plugin via the admin interface, and hit `Generate & Deploy` in the `Deploy` tab. 

## Documentation

Available filters to modify settings and data passed to the plugin:

- [Adjust additional files](#adjust-additional-files)
- [Adjust additional URLs](#adjust-additional-urls)
- [Adjust PHP max execution time](#adjust-php-max-execution-time)
- [Enable static directory clearing](#enable-static-directory-clearing)

Available actions to invoke or act upon:

- [Handle errors](#handle-errors)
- [Modify generated files](#modify-generated-files)
- [Schedule deploys](#schedule-deploys)

### Available filters

#### Adjust additional files

Modify entries from the 'Additional Files and Directories' option. By default all paths are temporarily resolved to absolute paths via [realpath](https://www.php.net/manual/en/function.realpath.php), to ensure symbolic links are resolved. An array of unmodified files from the options is passed as an argument.

```php
add_filter('grrr_simply_static_deploy_additional_files', function (array $files) {
    # Modify files, and possibly resolve paths with `realpath`.
    return $files;
});
```

Note: during generation of the static site, the `additional_files` setting is updated. It is restored when finished.

#### Adjust additional URLs

Modify entries from the 'Additional URLs' option. This can be useful to add pages that can't be found by Simply Static (not in the sitemap, are excluded by a password, have `noindex`, etc...). An array of unmodified URLs from the options is passed as an argument.

```php
add_filter('grrr_simply_static_deploy_additional_urls', function (array $urls) {
    # Modify urls, for example by adding missing pages.
    return $urls;
});
```

Note: during generation of the static site, the `additional_urls` setting is updated. It is restored when finished.

#### Adjust PHP max execution time

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
#### Enable static directory clearing

Clear the static folder before a new version is generated. Simply Static only allows you to delete the temporary files, but in some cases you might want to start with a clean slate (e.g. items deleted from the CMS, and a redirect being added in its place).

```php
add_filter('grrr_simply_static_deploy_clear_directory', function (bool $value) {
    return true;
});
```

Note: this setting is explicitly set by a filter, since it will completely delete any folder set as the `Local Directory`.

### Available actions

#### Handle errors

Called from the plugin, and receives a `WP_Error` object explaining the error. You can decide how to handle the error, for instance by logging it with a service of choice.

```php
add_action('grrr_simply_static_deploy_error', function (\WP_Error $error) {
    # Handle the error.
});
```

#### Modify generated files

Called when Simply Static is done generating the static site. This allows you to modify the generated files before they're being deployed. The static site directory is passed as an argument.

```php
add_action('grrr_simply_static_deploy_modify_generated_files', function (string $directory) {
    # Modify generated files, like renaming or moving them.
});
```

#### Schedule deploys

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
