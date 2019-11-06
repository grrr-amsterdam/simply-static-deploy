<?php namespace Grrr\SimplyStaticDeploy;

use WP_Error;

class Generator {

    const OPTION_TIMESTAMP_KEY = 'grrr_static_site_generated_at';

    /**
     * Start the Simply Static archive process tasks and return the result.
     *
     * @return WP_Error|bool
     */
    public function generate() {
        if (!Archive::is_completed()) {
            return new WP_Error('simply_static_error', 'Bundle generation already in progress. Someone else might\'ve started a deploy without your knowledge.', [
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
     * Return the configured output directory for the static site.
     *
     * @return string
     */
    public static function get_directory(): string {
        return Archive::get_directory() ?: '';
    }

    /**
     * Check wether Simply Static has completed its archive task list.
     *
     * @return bool
     */
    public static function is_completed(): bool {
        return Archive::is_completed();
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
