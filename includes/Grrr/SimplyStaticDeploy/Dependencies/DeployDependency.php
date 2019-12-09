<?php namespace Grrr\SimplyStaticDeploy\Dependencies;

class DeployDependency implements DependencyInterface {

    public function is_met(): bool {
        return defined('SIMPLY_STATIC_DEPLOY_AWS_CREDENTIALS');
    }

    public function register_notifications() {
        add_action('admin_notices', [$this, 'message_plugin_not_activated']);
    }

    public function message_plugin_not_activated() {
        $message = "Simply Static Deploy is missing the constant SIMPLY_STATIC_DEPLOY_AWS_CREDENTIALS.";

        printf('<div class="error"><p>%s</p></div>', $message);
    }
}
