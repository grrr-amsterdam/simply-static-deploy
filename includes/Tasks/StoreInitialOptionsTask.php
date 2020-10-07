<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Simply_Static\Task;
use Simply_Static\Util;

class StoreInitialOptionsTask extends Task {

    public function perform() {
        // get initial options
        // store them somewhere in the database

        Util::debug_log('Store initial options');
        return true;
    }
}
