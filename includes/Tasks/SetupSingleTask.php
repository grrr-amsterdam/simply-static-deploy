<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Grrr\SimplyStaticDeploy\Utils;
use Simply_Static\Page;
use Simply_Static\Plugin;
use Simply_Static\Setup_Task;
use Simply_Static\Task;
use Simply_Static\Util;

class SetupSingleTask extends Task
{
    /**
     * @var string
     */
    protected static $task_name = 'setup_single';

    public function perform(): bool
    {
        $post_id = get_option(Plugin::SLUG . '_single_deploy_id');
        $this->save_status_message(
            "Setting up for single post with id $post_id"
        );

        // should we create tmp directory of it not exists?
        $archive_dir = $this->options->get_archive_dir();

        // create temp archive directory
        if (!file_exists($archive_dir)) {
            Util::debug_log('creating archive directory: ' . $archive_dir);
            $create_dir = wp_mkdir_p($archive_dir);
            if ($create_dir === false) {
                return new \wp_error('cannot_create_archive_dir');
            }
        }

        Page::query()->delete_all();

        $url = get_permalink($post_id);

        Util::debug_log('Adding additional URL to queue: ' . $url);
        $static_page = Page::query()->find_or_initialize_by('url', $url);
        $static_page->set_status_message(__("Origin URL", 'simply-static'));
        // setting to 0 for "not found anywhere" since it's either the origin
        // or something the user specified
        $static_page->found_on_id = 0;
        $static_page->save();

        $additional_files = apply_filters(
            'simply_static_deploy_single_additional_files',
            [],
            $this->options
        );
        Util::debug_log('Adding ' . count($additional_files) . ' additional files via filter');
        Util::debug_log($additional_files);

        // The add_additional_files_to_db method accepts a list in the form of an textarea string
        // with new lines.
        $additional_files = Utils::array_to_option_string($additional_files);
        Setup_Task::add_additional_files_to_db($additional_files);

        // We should add the URL to the urls_to_exclude option with
        // do_not_follow = 1
        // That way we ONLY generate/save that single URL, but not following it
        // @see Simply_Static\Fetch_Urls_Task::find_excludable()
        $excludedUrls = array_merge($this->options->get('urls_to_exclude'), [
            [
                'url' => $url,
                'do_not_save' => '0',
                'do_not_follow' => '1',
            ],
        ]);
        $this->options->set('urls_to_exclude', $excludedUrls)->save();
        return true;
    }
}
