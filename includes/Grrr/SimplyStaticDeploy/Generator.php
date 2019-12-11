<?php namespace Grrr\SimplyStaticDeploy;

use WP_Error;

class Generator {

    const OPTION_TIMESTAMP_KEY = 'grrr_simply_static_deploy_generated_at';

    /**
     * Start the Simply Static archive process tasks and return the result.
     *
     * @return WP_Error|bool
     */
    public function generate() {
        if (Archive::is_in_progress()) {
            return new WP_Error('grrr_simply_static_deploy_generator', __("Bundle generation already in progress. Someone else might've started a deploy without your knowledge.", 'simply_static_deploy'), [
                'status' => 403,
            ]);
        }

        $archive = new Archive();
        $result = $archive->start();
        if (!$result instanceof WP_Error) {
            update_option(self::OPTION_TIMESTAMP_KEY, time());
        }

        return $result;
    }

    /**
     * Return the last archive generation time.
     *
     * @return string
     */
    public static function get_last_time(): string {
        return get_option(self::OPTION_TIMESTAMP_KEY) ?: '';
    }

}
