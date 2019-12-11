<?php
/**
 * Plugin Name: Simply Static Deploy
 * Plugin URI:  https://github.com/grrr-amsterdam/simply-static-deploy/
 * Description: Deploy static sites easily to an AWS S3 bucket.
 * Version:     0.1.0
 * Author:      GRRR
 * Author URI:  https://grrr.nl
 */
use Grrr\SimplyStaticDeploy\SimplyStaticDeploy;

// Global constants.
define('SIMPLY_STATIC_DEPLOY_VERSION', '0.1.0');
define('SIMPLY_STATIC_DEPLOY_URL', plugin_dir_url(__FILE__));
define('SIMPLY_STATIC_DEPLOY_PATH', plugin_dir_path(__FILE__));

// Require Composer autoloader.
require_once SIMPLY_STATIC_DEPLOY_PATH . 'vendor/autoload.php';

// Activation/Deactivation hooks.
register_activation_hook(__FILE__, '\Grrr\SimplyStaticDeploy\Core\activate');
register_deactivation_hook(__FILE__, '\Grrr\SimplyStaticDeploy\Core\deactivate');

// Initialize the plugin.
(new SimplyStaticDeploy)->init();
