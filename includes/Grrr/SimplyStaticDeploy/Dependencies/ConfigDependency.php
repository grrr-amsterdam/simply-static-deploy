<?php namespace Grrr\SimplyStaticDeploy\Dependencies;

use Grrr\SimplyStaticDeploy\Config;

class ConfigDependency implements DependencyInterface {

    private $constName;

    public function __construct(string $constName) {
        $this->constName = $constName;
    }

    public function is_met(): bool {
        return defined($this->constName);
    }

    public function register_notifications() {
        add_action('admin_notices', [$this, 'message_config_undefined']);
    }

    public function message_config_undefined() {
        $message = "Simply Static Deploy is missing the constant {$this->constName}.";

        printf('<div class="error"><p>%s</p></div>', $message);
    }

}
