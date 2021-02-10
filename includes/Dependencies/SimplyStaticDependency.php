<?php namespace Grrr\SimplyStaticDeploy\Dependencies;

class SimplyStaticDependency implements DependencyInterface
{
    public function is_met(): bool
    {
        // https://waclawjacek.com/check-wordpress-plugin-dependencies/
        $activePlugins = apply_filters('active_plugins', get_option('active_plugins'));
        return in_array('simply-static/simply-static.php', $activePlugins);
    }

    public function register_notifications()
    {
        add_action('admin_notices', [$this, 'message_plugin_not_activated']);
    }

    public function message_plugin_not_activated()
    {
        $message =
            "Simply Static Deploy is dependent on the <a href=\"https://wordpress.org/plugins/simply-static/\">Simply Static plugin</a>. Install and activate the required plugin.";

        printf('<div class="error"><p>%s</p></div>', $message);
    }
}
