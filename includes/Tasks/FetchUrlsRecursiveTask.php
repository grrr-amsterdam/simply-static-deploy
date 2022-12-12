<?php
namespace Grrr\SimplyStaticDeploy\Tasks;

use Exception;
use Simply_Static\Fetch_Urls_Task;
use Simply_Static\Page;
use Simply_Static\Plugin;
use Simply_Static\Util;
use Simply_Static\Url_Fetcher;
use WP_Error;

/**
 * Class which handles fetch recursive urls task; all urls beneath the current post_id.
 * Based on Fetch_Urls_Task with some custom code blocks.
 */
class FetchUrlsRecursiveTask extends Fetch_Urls_Task
{
    /**
     * Task name.
     *
     * @var string
     */
    public static $task_name = 'fetch_urls_recursive';

    /**
     * Fetch and save pages for the static archive
     *
     * Note: this is a copy from code from Fetch_Urls_Taks with additional custom code blocks. See comments.
     *
     * @return boolean|WP_Error true if done, false if not done, WP_Error if error.
     * @throws Exception
     */
    public function perform()
    {
        $batch_size = apply_filters('simply_static_fetch_urls_batch_size', 10);
        // Custom code block
        $post_id = get_option(Plugin::SLUG . '_single_deploy_id');
        $post_url = get_permalink($post_id);

        $this->save_status_message(
            "Fetching urls recursively from post with post id: ${post_id} and url: ${post_url}."
        );
        // End of custom code block

        $static_pages = apply_filters(
            'ss_static_pages',
            Page::query()
                ->where('last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time)
                ->limit($batch_size)
                ->find(),
            $this->archive_start_time
        );

        $pages_remaining = apply_filters(
            'ss_remaining_pages',
            Page::query()
                ->where('last_checked_at < ? OR last_checked_at IS NULL', $this->archive_start_time)
                ->count(),
            $this->archive_start_time
        );

        $total_pages = apply_filters('ss_total_pages', Page::query()->count());

        $pages_processed = $total_pages - $pages_remaining;
        Util::debug_log("Total pages: " . $total_pages . '; Pages remaining: ' . $pages_remaining);

        while ($static_page = array_shift($static_pages)) {
            // Custom code block
            $url = $static_page->url;
            Util::debug_log("URL: " . $url);

            $excludable = apply_filters(
                'simply_static_deploy_recursive_excludable',
                $this->find_excludable($static_page),
                $url,
                $post_url
            );
            // End of custom code block

            if ($excludable !== false) {
                $save_file = $excludable['do_not_save'] !== '1';
                $follow_urls = $excludable['do_not_follow'] !== '1';

                Util::debug_log("Excludable found: URL: " . $excludable['url'] . ' DNS: ' . $excludable['do_not_save'] . ' DNF: ' . $excludable['do_not_follow']);
            } else {
                $save_file = true;
                $follow_urls = true;
                Util::debug_log("URL is not being excluded");
            }

            // If we're not saving a copy of the page or following URLs on that
            // page, then we don't need to bother fetching it.
            if ($save_file === false && $follow_urls === false) {
                Util::debug_log("Skipping URL because it is no-save and no-follow");
                $static_page->last_checked_at = Util::formatted_datetime();
                $static_page->set_status_message(__("Do not save or follow", 'simply-static'));
                $static_page->save();
                continue;
            }

            /**
             *  Custom code block
             *  Note: also replaced unnecessary else clause from if statement above.
             */
            // Windows support.
            if (strpos( $url, '\\' ) !== false || strpos( $url, '\\' ) !== false) {
                $url = str_replace( '\\', '/', $url );
            }
            // Only fetch urls if url contains url from parent post_id
            if (
                !Util::is_local_url($url) ||
                strpos($url, $post_url) === false
            )
            {
                $static_page->http_status_code = null;
                $static_page->last_checked_at = Util::formatted_datetime();
                $static_page->set_status_message('Not fetching, because url does not contain url from parent post.');
                $static_page->save();
                continue;
            }

            if(!Url_Fetcher::instance()->fetch($static_page)) {
                continue;
            }
            // End of custom code block

            // If we get a 30x redirect...
            if (in_array($static_page->http_status_code, array(301, 302, 303, 307, 308))) {
                $this->handle_30x_redirect($static_page, $save_file, $follow_urls);
                continue;
            }

            // Not a 200 for the response code? Move on.
            if ($static_page->http_status_code != 200) {
                continue;
            }

            $this->handle_200_response($static_page, $save_file, $follow_urls);

            do_action('ss_after_setup_static_page', $static_page);

        }

        $message = sprintf(__("Fetched %d of %d pages/files", 'simply-static'), $pages_processed, $total_pages);
        $this->save_status_message($message);

        // if we haven't processed any additional pages, we're done.
        if ($pages_remaining == 0) {
            do_action('ss_finished_fetching_pages');
        }

        return $pages_remaining == 0;
    }
}

