<?php namespace Grrr\SimplyStaticDeploy;

use Simply_Static;
use Garp\Functional as f;

class SettingsEnforcer {

    const SETTINGS_FILTER = 'grrr_simply_static_deploy_settings';

    private $settings = [];

    public function __construct() {
        if (!has_filter(static::SETTINGS_FILTER)) {
            return;
        }
        $this->settings = apply_filters(
            static::SETTINGS_FILTER,
            Simply_Static\Options::instance()->get_as_array()
        );
    }

    public function enforce() {
        if (!$this->settings) {
            return;
        }
        foreach ($this->settings as $key => $value) {
            Simply_Static\Options::instance()->set($key, $value);
        }
        Simply_Static\Options::instance()->save();
    }

}
