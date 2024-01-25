# Simply Static Deploy

<!--[![Build Status](https://travis-ci.com/grrr-amsterdam/simply-static-deploy.svg?branch=master)](https://travis-ci.com/grrr-amsterdam/simply-static-deploy)-->

### This repository is archived

❗️ GRRR no longer maintains this plugin.

We recommend using the Pro plan of the [Simply Static](https://simplystatic.com/) plugin. When we started this plugin, it fixed the lack of a deployment feature in Simply Static. Since then, the plugin has been updated, and the Pro plan offers exactly what we were missing.  
GRRR has contributed code and features to the Simply Static plugin, and we're happy to see it grow.  
We suggest taking a look, it's well worth the investment.

Thanks to everyone who has taken an interest in this plugin!  
If you've enjoyed using this plugin or were inspired by it in any way, maybe you'd like to follow our blog, where we write about our work and the things we learn along the way: [grrr.tech](https://grrr.tech/).

---

### Deploy static sites easily to an AWS S3 bucket

-   Utilizes the excellent [Simply Static](https://wordpress.org/plugins/simply-static/) plugin for static site generation.
-   Adds deployment to S3-compatible storage (AWS S3, DigitalOcean Spaces, ...).
-   Adds optional CloudFront CDN invalidation step.
-   Steps can be triggered via a simple user interface or programmatically.
-   Ability to generate and deploy a single page including recursive option
-   Customizable using hooks and actions.

### Developed with ❤️ by [GRRR](https://grrr.nl)

-   GRRR is a [B Corp](https://grrr.nl/en/b-corp/)
-   GRRR has a [tech blog](https://grrr.tech/)
-   GRRR is [hiring](https://grrr.nl/en/jobs/)
-   [@GRRRTech](https://twitter.com/grrrtech) tweets

#### Generate and deploy user interface

<img width="568" alt="Screenshot of Simply Static Deploy plugin interface for WordPress" src="https://user-images.githubusercontent.com/1799286/107524304-e9c22700-6bb5-11eb-8df1-1ded03b16df0.png">

#### Single page/post deploy user interface

<img width="292" alt="Screenshot of plugin interface for deploying a single page" src="https://user-images.githubusercontent.com/884784/177352714-9b42b8b2-33dc-4f99-adf1-572cc9cf6ebb.png">

## Minimum requirements

This plugin requires:

-   A minimum PHP version of **7.1**.
-   An installed and activated version of the [Simply Static plugin](https://wordpress.org/plugins/simply-static/).

## Installation

This plugin needs to be installed using [Composer](https://getcomposer.org/).

Make sure you have the right installer paths configured in your `composer.json`. This has to be done before requiring the package:

```json
"extra": {
  "installer-paths": {
    "wp-content/plugins/{$name}/": ["type:wordpress-plugin"]
  }
}
```

Install via Composer:

```sh
$ composer require grrr-amsterdam/simply-static-deploy
```

If you're not using Composer in your project yet, make sure to require the [Composer autoloader](https://getcomposer.org/doc/01-basic-usage.md#autoloading). A good place would be in your `wp-config.php`:

```php
/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'vendor/autoload.php'; # ‹— add this
require_once ABSPATH . 'wp-settings.php';
```

## Usage

First define `SIMPLY_STATIC_DEPLOY_CONFIG` in your WordPress configuration:

```php
define('SIMPLY_STATIC_DEPLOY_CONFIG', [
    'aws' => [
        'key' => '...', # AWS access key
        'secret' => '...', # AWS secret key
        'region' => '...', # AWS region
        'bucket' => '...', # S3 bucket
        'bucket_acl' => '...', # S3 bucket ACL (optional, defaults to `public-read`)
        'distribution' => '...', # CloudFront distribution ID (optional, step is skipped when empty)
        'endpoint' => '...', # For usage with providers other than AWS (optional)
    ],
    'url' => '...', # Website url (used for displaying url after deploy is finished)
]);
```

Then configure the Simply Static plugin via the admin interface. The most important setting to get right is:

-   `Delivery Method`: set to `Local Directory` (files are synced to S3, zip won't work)

Other settings which you should pay attention to:

-   `Additional URLs`: add any URL the plugin is unable to find
-   `Additional Files and Directories`: add additional directories (for example front-end assets)
-   `URLs to Exclude`: for example the uploads folder (but only when you're offloading uploads at runtime)

If everything is configured correctly, hit `Generate & Deploy` in the `Deploy` tab.

## Documentation

### Features

#### Single post deploy

Pages/posts come with single deploy button, so that a single page can be generated and deployed, see single page/post deploy user interface.

#### Single recursive deploy

A single post or page deploy can also be done recursively by checking the recursive option, see checkbox in single page/post deploy user interface. When 'recursive' has been checked all pages/posts that containt the url of the current page/post will be generated and deployed as well.

### Available filters and actions

Available filters to modify settings and data passed to the plugin:

-   [Adjust additional files](#adjust-additional-files)
-   [Adjust additional files for Single deploy](#adjust-additional-files-for-single-deploy)
-   [Adjust additional URLs](#adjust-additional-urls)
-   [Recursive excludable]()

Available actions to invoke or act upon:

-   [Handle errors](#handle-errors)
-   [Completed static deploy job](#completed-static-deploy-job)
-   [Modify generated files](#modify-generated-files)
-   [Schedule deploys](#schedule-deploys)

#### Filters

##### Adjust additional files

Modify entries from the 'Additional Files and Directories' option. By default all paths are temporarily resolved to absolute paths via [realpath](https://www.php.net/manual/en/function.realpath.php), to ensure symbolic links are resolved. An array of unmodified files from the options is passed as an argument.

```php
add_filter('simply_static_deploy_additional_files', function (array $files) {
    # Modify files, and possibly resolve paths with `realpath`.
    return $files;
});
```

Note: during generation of the static site, the `additional_files` setting is updated. It is restored when finished.

##### Adjust additional files for Single deploy

When doing a single deploy only the given page/post will be generated, including the files given in the Simply Static 'Additional files' setting. You can change these additional files for single deploys via the `simply_static_deploy_single_additional_files` filter. It takes two arguments: the first one is an array of filenames, the second one is the Simply Static Options instance.

#### Adjust additional URLs

Modify entries from the 'Additional URLs' option. This can be useful to add pages that can't be found by Simply Static (not in the sitemap, are excluded by a password, have `noindex`, etc...). An array of unmodified URLs from the options is passed as an argument.

```php
add_filter('simply_static_deploy_additional_urls', function (array $urls) {
    # Modify urls, for example by adding missing pages.
    return $urls;
});
```

Note: during generation of the static site, the `additional_urls` setting is updated. It is restored when finished.

#### Modify Recursive excludable

This filter adds the option to customize the excludable url setting. This can be useful when for instance you want to ignore exclusions when an url contains the recursive parent url.

```php
add_filter('simply_static_deploy_recursive_excludable', function (
    $excludable,
    string $staticPageUrl,
    string $recursiveUrl
) {
    # Modify excludable url logic, for example ignore excludeable url setting when current page contains the recursiveUrl
    return $excludable;
});
```

#### Actions

##### Handle errors

Called from the plugin, and receives a `WP_Error` object explaining the error. You can decide how to handle the error, for instance by logging it with a service of choice.

```php
add_action('simply_static_deploy_error', function (\WP_Error $error) {
    # Handle the error.
});
```

##### Completed static deploy job

This will be triggered after all deploy tasks are finished. The first and only argument you will get
in the callback function is the Simply Static options instance.

```
add_action('simply_static_deploy_complete' , function (\Simply_Static\Options $options) {
    // Finished static deploy job.
});
```

##### Modify generated files

Called when Simply Static is done generating the static site. This allows you to modify the generated files before they're being deployed. The static site directory is passed as an argument.

```php
add_action('simply_static_deploy_modify_generated_files', function (
    string $directory
) {
    # Modify generated files, like renaming or moving them.
});
```

##### Schedule deploys

Schedule a deploy event.

Arguments:

-   **Time**: should be a simple time string, it is automatically converted to a UNIX timestamp in the configured WordPress timezone.
-   **Interval**: accepted values are `hourly`, `twicedaily` and `daily`. Can be extended via [cron_schedules](https://developer.wordpress.org/reference/hooks/cron_schedules).

```php
do_action('simply_static_deploy_schedule', '12:00', 'daily');
```

Note: it is important that [WP-Cron](https://developer.wordpress.org/plugins/cron/) is called regularly. You could do so by disabling the default WP-Cron mechanism and switch to calling it via a dedicated [cronjob](https://en.wikipedia.org/wiki/Cronjob).

To disable the default WP–Cron (which is normally called when a user visits pages), add the following to your WordPress configuration:

```php
define('DISABLE_WP_CRON', true);
```

Create a cronjob calling the WordPres WP-Cron. Setting it to _every 5 minutes_ would be a good default. For example via `crontab -e` on a Linux machine:

```cron
*/5 * * * * curl https://example.com/wp/wp-cron.php?doing-cron > /dev/null 2>&1
```

### Common issues

```
Fatal error: Uncaught Error: Class 'Grrr\SimplyStaticDeploy\SimplyStaticDeploy' not found
```

Check the [installation instructions](#installation), and require the Composer autoloader in your project.
