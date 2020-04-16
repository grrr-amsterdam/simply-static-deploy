<?php
/**
 * Plugin Name: Simply Static Deploy
 * Plugin URI:  https://github.com/grrr-amsterdam/simply-static-deploy/
 * Description: Deploy static sites easily to an AWS S3 bucket.
 * Version:     1.0.0
 * Author:      GRRR
 * Author URI:  https://grrr.nl
 */
use Grrr\SimplyStaticDeploy\SimplyStaticDeploy;

// Global constants.
define('SIMPLY_STATIC_DEPLOY_VERSION', '1.0.0');
define('SIMPLY_STATIC_DEPLOY_PATH', plugin_dir_path(__FILE__));
define('SIMPLY_STATIC_DEPLOY_URL', plugin_dir_url(__FILE__));

// Require Composer autoloader when it exists.
// For regular installs it won't exist, for local development it might exist.
$autoloader = rtrim(SIMPLY_STATIC_DEPLOY_PATH, '/') . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Initialize the plugin.
(new SimplyStaticDeploy(
    SIMPLY_STATIC_DEPLOY_PATH,
    SIMPLY_STATIC_DEPLOY_URL,
    SIMPLY_STATIC_DEPLOY_VERSION
))->init();
