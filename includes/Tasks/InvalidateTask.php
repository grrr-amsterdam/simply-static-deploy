<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Grrr\SimplyStaticDeploy\Config;
use Grrr\SimplyStaticDeploy\Invalidator;
use Grrr\SimplyStaticDeploy\SimplyStaticDeploy;
use Simply_Static\Task;
use Simply_Static\Util;

class InvalidateTask extends Task
{
    protected static $task_name = 'invalidate';

    public function perform()
    {
        Util::debug_log('Invalidate the Cloudfront distribution.');
        $this->save_status_message('Invalidating cloudfront');
        $config = new Config(constant(SimplyStaticDeploy::CONFIG_CONST));
        $invalidator = new Invalidator($config->aws);
        return $invalidator->invalidate();
    }
}
