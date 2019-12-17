<?php namespace Grrr\SimplyStaticDeploy\Dependencies;

interface DependencyInterface {

    /**
     * Check whether this dependency is met.
     *
     * @return bool
     */
    public function is_met();

    /**
     * Registers the notifications to communicate the dependency is not met.
     */
    public function register_notifications();

}
