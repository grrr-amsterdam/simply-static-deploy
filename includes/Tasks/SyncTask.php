<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Grrr\SimplyStaticDeploy\Config;
use Grrr\SimplyStaticDeploy\SimplyStaticDeploy;
use Grrr\SimplyStaticDeploy\StaticDeployJob;
use Grrr\SimplyStaticDeploy\Syncer;
use Simply_Static\Task;
use Simply_Static\Util;

class SyncTask extends Task
{
    protected static $task_name = 'sync';

    public function perform()
    {
        Util::debug_log('Sync site to s3');
        $this->save_status_message('Syncing files to S3');
        $config = new Config(constant(SimplyStaticDeploy::CONFIG_CONST));

        $syncer = new Syncer($config->aws);
        $path = StaticDeployJob::get_directory();

        return $syncer->sync($path);
    }
}
