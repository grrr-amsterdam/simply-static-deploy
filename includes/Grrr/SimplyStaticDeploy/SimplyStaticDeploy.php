<?php namespace Grrr\SimplyStaticDeploy;

class SimplyStaticDeploy {

    const CONFIG_CONST = 'SIMPLY_STATIC_DEPLOY_CONFIG';

    private $basePath;

    public function __construct(string $basePath) {
        $this->basePath = $basePath;
    }

    public function init() {
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
    }

    public function plugins_loaded() {
        $requirements = new DependencyList;
        $requirements->add_dependency(new Dependencies\SimplyStaticDependency());
        $requirements->add_dependency(new Dependencies\ConfigDependency(self::CONFIG_CONST));

        if (!$requirements->are_met()) {
            return;
        }

        $config = new Config(constant(self::CONFIG_CONST));

        // Bootstrap components.
        (new Admin($config))->register($this->basePath);
        (new Api($config))->register();
        (new Scheduler($config))->register();
    }

}
