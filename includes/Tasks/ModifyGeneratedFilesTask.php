<?php namespace Grrr\SimplyStaticDeploy\Tasks;

use Simply_Static\Options;
use Simply_Static\Task;
use Simply_Static\Util;

class ModifyGeneratedFilesTask extends Task {

    const MODIFY_ACTION = 'simply_static_deploy_modify_generated_files';

    public function perform() {
        Util::debug_log('Modify generated files');
        $this->save_status_message(
            "Modify generated files"
        );
        $localDir = Options::instance()->get('local_dir') ?: '';
        do_action(static::MODIFY_ACTION, $localDir);
        return true;
    }
}
