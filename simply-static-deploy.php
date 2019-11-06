<?php
/**
 * Plugin Name: Simply Static Deploy
 * Plugin URI:
 * Description: Deploy static sites easily to an AWS S3 bucket.
 * Version:     0.1.0
 * Author:      GRRR
 * Author URI:  https://grrr.nl
 *
 * @package Grrr
 */

// Useful global constants.
define('SIMPLY_STATIC_DEPLOY_VERSION', '0.1.0');
define('SIMPLY_STATIC_DEPLOY_URL', plugin_dir_url(__FILE__));
define('SIMPLY_STATIC_DEPLOY_PATH', plugin_dir_path(__FILE__));

// Include files.
require_once SIMPLY_STATIC_DEPLOY_PATH . '/includes/functions/core.php';

// Activation/Deactivation.
register_activation_hook(__FILE__, '\Grrr\SimplyStaticDeploy\Core\activate' );
register_deactivation_hook(__FILE__, '\Grrr\SimplyStaticDeploy\Core\deactivate' );

// Bootstrap.
Grrr\SimplyStaticDeploy\Core\setup();

// Require Composer autoloader if it exists.
if (file_exists(SIMPLY_STATIC_DEPLOY_PATH . '/vendor/autoload.php')) {
	require_once SIMPLY_STATIC_DEPLOY_PATH . 'vendor/autoload.php';
}
