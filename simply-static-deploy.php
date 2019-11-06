<?php
/**
 * Plugin Name: Simply Static Deploy
 * Plugin URI:
 * Description: Deploy static sites easily to an AWS S3 bucket.
 * Version:     0.1.0
 * Author:      GRRR
 * Author URI:  https://grrr.nl
 */
use Grrr\SimplyStaticDeploy\{Admin, Api, Scheduler};

// Useful global constants.
// TODO Is this necessary?
define('SIMPLY_STATIC_DEPLOY_VERSION', '0.1.0');
define('SIMPLY_STATIC_DEPLOY_URL', plugin_dir_url(__FILE__));
define('SIMPLY_STATIC_DEPLOY_PATH', plugin_dir_path(__FILE__));


// Require Composer autoloader.
require_once SIMPLY_STATIC_DEPLOY_PATH . '/vendor/autoload.php';

// Activation/Deactivation.
// TODO Do we need this?
//register_activation_hook(__FILE__, '\Grrr\SimplyStaticDeploy\Core\activate' );
//register_deactivation_hook(__FILE__, '\Grrr\SimplyStaticDeploy\Core\deactivate' );

// Bootstrap components.
(new Admin)->register(SIMPLY_STATIC_DEPLOY_PATH);
(new Api)->register();
(new Scheduler)->register();
