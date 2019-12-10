<?php namespace Grrr\SimplyStaticDeploy\Dependencies;

class ConfigDependency implements DependencyInterface {

    public function is_met(): bool {
        return defined('SIMPLY_STATIC_DEPLOY_CONFIG');
    }

    public function register_notifications() {
        add_action('admin_notices', [$this, 'message_config_undefined']);
    }

    public function message_config_undefined() {
        $message = "Simply Static Deploy is missing the constant SIMPLY_STATIC_DEPLOY_CONFIG.";

        printf('<div class="error"><p>%s</p></div>', $message);
    }

}
