<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Garp\Functional as f;
use Simply_Static\Options;
use Simply_Static\Plugin;
use Simply_Static\Task;
use Simply_Static\Util;

class RestoreInitialOptionsTask extends Task
{
    protected static $task_name = 'restore_initial_options';

    public function perform()
    {
        Util::debug_log('Restore initial options');
        $this->save_status_message('Restoring initial settings');

        $initialOptions = get_option(Plugin::SLUG . '_tmp_options');

        $this->options->set(
            'additional_files',
            f\prop('additional_files', $initialOptions)
        );
        $this->options->set(
            'additional_urls',
            f\prop('additional_urls', $initialOptions)
        );
        $this->options->set(
            'urls_to_exclude',
            f\prop('urls_to_exclude', $initialOptions)
        );
        $this->options->save();

        return true;
    }
}
