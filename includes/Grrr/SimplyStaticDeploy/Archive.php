<?php namespace Grrr\SimplyStaticDeploy;

use WP_Error;
use Simply_Static;
use Garp\Functional as f;

/**
 * Archive wrapper for Simply_Static archive tasks.
 * Invokes tasks synchronously, and circumvents usage of WP_Background_Process.
 *
 * @author Koen Schaft <koen@grrr.nl>
 */
class Archive {

    const MODIFY_ACTION = 'grrr_simply_static_deploy_modify_generated_files';
    const PHP_FILTER = 'grrr_simply_static_deploy_php_execution_time';

    private $tasks = [];
    private $filesOption = '';
    private $urlsOption = '';

    public function __construct() {
        // Increase max execution time for this class, since a larger website can
        // take quite a while for it to be fully scraped and 'archived'.
        $executionTime = apply_filters(static::PHP_FILTER, 600);
        ini_set('max_execution_time', $executionTime);

        // Add all tasks in the right order.
        $this->tasks = [
            new Simply_Static\Setup_Task(),
            new Simply_Static\Fetch_Urls_Task(),
            new Simply_Static\Transfer_Files_Locally_Task(),
            new Simply_Static\Wrapup_Task(),
        ];

        // Store original additional_files and `additional_urls` options, so we can
        // restore them later. We do so, because we need to update the options to
        // resolve symbolic links and add 'hidden' posts.
        $this->filesOption = Simply_Static\Options::instance()->get('additional_files');
        $this->urlsOption = Simply_Static\Options::instance()->get('additional_urls');
    }

    /**
     * Start the archive process and return result.
     *
     * @return WP_Error|bool
     */
    public function start() {
        $this->clear_directory(static::get_directory());

        $this->set_start_options();
        $result = $this->run_tasks($this->tasks);
        $this->set_end_options();

        if ($result instanceof WP_Error) {
            do_action('grrr_simply_static_deploy_error', $result);
            return $result;
        } else {
            if (has_action(static::MODIFY_ACTION)) {
                do_action(static::MODIFY_ACTION, static::get_directory());
            } else {
                $this->modify_generated_files(static::get_directory());
            }
            return true;
        }
    }

    /**
     * Return the configured output directory for the static site.
     *
     * @return string
     */
    public static function get_directory(): string {
        return Simply_Static\Options::instance()->get('local_dir') ?: '';
    }

    /**
     * Check whether Simply Static is in progress.
     * This is defined by there being a start_time stored but not an end_time.
     *
     * @return bool
     */
    public static function is_in_progress(): bool {
        return Simply_Static\Options::instance()->get('archive_start_time')
            && !Simply_Static\Options::instance()->get('archive_end_time');
    }

    /**
     * Run all tasks.
     *
     * @return WP_Error|bool
     */
    private function run_tasks(array $tasks) {
        foreach ($tasks as $task) {
            $result = $this->perform_task($task);
            if ($result instanceof WP_Error) {
                return $result;
            }
            if (!$result) {
                $message = 'Something went wrong in Simply Static: ' . get_class($task);
                return new WP_Error('simply_static_error', $message, [
                    'status' => 403,
                ]);
            }
        }
        return true;
    }

    /**
     * Perform task and perform it again when it's not finished.
     *
     * @return Task|bool
     */
    private function perform_task($task) {
        $result = $task->perform();
        return $result ?: $this->perform_task($task);
    }

    /**
     * Set and store Simply Static start options.
     */
    private function set_start_options(): void {
        Simply_Static\Options::instance()
            ->set('archive_status_messages', [])
            ->set('additional_files', $this->resolve_additional_files($this->filesOption))
            ->set('additional_urls', $this->enrich_additional_urls($this->urlsOption))
            ->set('archive_start_time', Simply_Static\Util::formatted_datetime())
            ->set('archive_end_time', '')
            ->save();
    }

    /**
     * Set and store Simply Static end options.
     */
    private function set_end_options(): void {
        Simply_Static\Options::instance()
            ->set('additional_files', $this->filesOption)
            ->set('additional_urls', $this->urlsOption)
            ->set('archive_end_time', Simply_Static\Util::formatted_datetime())
            ->save();
    }

    /**
     * Clear the current static site directory, to make sure deleted pages
     * are not deployed again, and potentialy overwriting redirects.
     */
    private function clear_directory(string $dir): void {
        $name = f\last(f\split('/', trim($dir, '/')));
        if (!$dir || !file_exists($dir) || $name !== 'static') {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }

    /**
     * Resolve all symbolic links to absolute/real paths, because we call
     * the plugin via the REST API. Since we have installed the site in a
     * subdirectory (`/wp`) the `get_home_path()` function returns nothing.
     * See: https://wordpress.stackexchange.com/a/188461
     *
     * On the server our site is installed via a symlinked release. Since our
     * `additional_files` setting in the CMS needs to be persistent, we set it
     * to the `/current` path. The WordPress constants in the plugin return the
     * absolute paths (`/release/2893479823/`) resulting in a mismatch in the plugin.
     *
     * We're fixing this by temporarily resolving all `additional_files` paths
     * to absolute URLs with resolved symlinks, and restoring them after completion.
     */
    private function resolve_additional_files(string $option): string {
        $paths = Simply_Static\Util::string_to_array($option);
        return f\join(PHP_EOL, f\unique(f\map('realpath', $paths)));
    }

    /**
     * Pages or posts which aren't linked to and which are unavailable in the
     * sitemap will not be fetched, since Simply Static can't find them.
     * We'll have to fetch them manually and append them to the `additional_urls`
     * option during the archive tasks.
     */
    private function enrich_additional_urls(string $option): string {
        $urls = Simply_Static\Util::string_to_array($option);
        return f\join(PHP_EOL, f\unique(f\concat(
            $urls,
            $this->fetch_password_protected_posts(),
            $this->fetch_yoast_noindex_posts()
        )));
    }

    /**
     * Fetch password protected posts.
     */
    private function fetch_password_protected_posts(): array {
        $query = new \WP_Query([
            'post_type' => 'any',
            'has_password' => true,
            'posts_per_page' => -1,
        ]);
        return f\map(f\compose('get_permalink', f\prop('ID')), $query->posts);
    }

    /**
     * Fetch posts which are set to `noindex` by Yoast SEO.
     */
    private function fetch_yoast_noindex_posts(): array {
        $query = new \WP_Query([
            'post_type' => 'any',
            'meta_key' => '_yoast_wpseo_meta-robots-noindex',
            'meta_value_num' => 1,
            'posts_per_page' => -1,
        ]);
        return f\map(f\compose('get_permalink', f\prop('ID')), $query->posts);
    }

    /**
     * Modify generated files since they will be hosted statically.
     */
    private function modify_generated_files(string $directory): void {
        $cwd = rtrim($directory, '/');

        // The `sitemap.xml` generated by Yoast redirects to `sitemap_index.xml`.
        // We rename it so it becomes the main sitemap.
        if (file_exists($cwd . '/sitemap_index.xml')) {
            rename($cwd . '/sitemap_index.xml', $cwd . '/sitemap.xml');
        }

        // The RSS/Atom feeds are XML files, but these can't be document roots on S3.
        // We'll rename them to HTML files, and apply the proper MIME type during transfer.
        if (file_exists($cwd . '/feed/index.xml')) {
            rename($cwd . '/feed/index.xml', $cwd . '/feed/index.html');
        }
        if (file_exists($cwd . '/feed/atom/index.xml')) {
            rename($cwd . '/feed/atom/index.xml', $cwd . '/feed/atom/index.html');
        }
    }
}
