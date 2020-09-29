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
        $allAreMet = f\reduce(
            function ($previousAreMet, $dependency) {
                $isMet = $dependency->is_met();
                if (!$isMet) {
                    $dependency->register_notifications();
                    return f\reduced(false);
                }
                return true;
            },
            true,
            $this->dependencies
        );

        return $allAreMet;
    }
}
