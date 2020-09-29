<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Simply_Static\Options;
use Simply_Static\Page;
use Simply_Static\Task;
use Simply_Static\Util;

class SetupSingleTask extends Task
{
    /**
     * @var string
     */
    protected static $task_name = 'setup_single';

    protected $post_id;

    public function __construct(int $post_id)
    {
        $this->post_id = $post_id;
        parent::__construct();
    }

    public function perform(): bool
    {
        Page::query()->delete_all();

        $url = get_permalink($this->post_id);

        Util::debug_log('Adding additional URL to queue: ' . $url);
        $static_page = Page::query()->find_or_initialize_by('url', $url);
        $static_page->set_status_message(__("Origin URL", 'simply-static'));
        // setting to 0 for "not found anywhere" since it's either the origin
        // or something the user specified
        $static_page->found_on_id = 0;
        $static_page->save();

        // We should add the URL to the urls_to_exclude option with
        // do_not_follow = 1
        // That way we ONLY generate that single URL.
        // @see Simply_Static\Fetch_Urls_Task::find_excludable()
        $excludedUrls = array_merge(
            Options::instance()->get('urls_to_exclude'),
            [
                [
                    'url' => $url,
                    'do_not_save' => '0',
                    'do_not_follow' => '1',
                ],
            ]
        );
        Options::instance()->set(
            'urls_to_exclude',
            Utils::array_to_option_string($excludedUrls)
        );
    }
}
