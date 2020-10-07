<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Simply_Static\Util;

class RestoreInitialOptionsTask {


    public function perform() {
        // get initial options
        // store them somewhere in the database

        Util::debug_log('Restore initial options');
        return true;
    }
}
