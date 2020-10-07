<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Simply_Static\Util;

class SyncTask {

    public function perform() {
        Util::debug_log('Sync shizzle to s3');
        return true;
    }
}
