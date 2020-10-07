<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Simply_Static\Util;

class InvalidateTask {
    public function perform() {
        Util::debug_log('Invalidate the shizzle');
        return true;
    }
}
