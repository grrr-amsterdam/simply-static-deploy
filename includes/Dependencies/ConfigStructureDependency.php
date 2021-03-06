<?php namespace Grrr\SimplyStaticDeploy\Dependencies;

use Grrr\SimplyStaticDeploy\Config\RequiredFields;

class ConfigStructureDependency implements DependencyInterface
{
    private $constName;

    public function __construct(string $constName)
    {
        $this->constName = $constName;
    }

    public function is_met(): bool
    {
        return empty(
            RequiredFields::get_missing_options(
                defined($this->constName) ? constant($this->constName) : []
            )
        );
    }

    public function register_notifications()
    {
        add_action('admin_notices', [$this, 'message_config_undefined']);
    }

    public function message_config_undefined()
    {
        $configValue = defined($this->constName)
            ? constant($this->constName)
            : [];
        $message = sprintf(
            "Simply Static Deploy is missing the following configuration options: %s.",
            implode(', ', RequiredFields::get_missing_options($configValue))
        );

        printf('<div class="error"><p>%s</p></div>', $message);
    }
}
