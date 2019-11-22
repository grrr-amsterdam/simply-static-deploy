<?php namespace Grrr\SimplyStaticDeploy;

use Grrr\SimplyStaticDeploy\Dependencies\DependencyInterface;
use Garp\Functional as f;

class DependencyList
{
    protected $dependencies = [];

    public function add_dependency(DependencyInterface $dependency)
    {
        $this->dependencies[] = $dependency;
    }

    public function are_met(): bool
    {
        $can_manage_plugins = current_user_can('activate_plugins');

        $all_are_met = f\reduce(
            function ($previous_are_met, $dependency) use ($can_manage_plugins) {
                $is_met = $dependency->is_met();
                if (!$is_met && $can_manage_plugins ) {
                    $dependency->register_notifications();
                }
                return $previous_are_met && $is_met;
            },
            true,
            $this->dependencies
        );

        return $all_are_met;
    }
}