<?php namespace Grrr\SimplyStaticDeploy;

class SimplyStaticDeploy
{
    public function init()
    {
        add_action('admin_init', [$this, 'admin_init']);
    }

    public function admin_init()
    {
        $requirements = new DependencyList;
        $requirements->add_dependency(new Dependencies\SimplyStaticDependency);
        $requirements->add_dependency(new Dependencies\DeployDependency);

        if (!$requirements->are_met()) {
            return;
        }

        $config = new Config(SIMPLY_STATIC_DEPLOY_AWS_CREDENTIALS);

        // Bootstrap components.
        (new Admin($config))->register(SIMPLY_STATIC_DEPLOY_PATH);
        (new Api($config))->register();
        (new Scheduler($config))->register();
    }

}