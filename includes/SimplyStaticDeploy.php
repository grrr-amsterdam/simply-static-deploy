<?php namespace Grrr\SimplyStaticDeploy;

class SimplyStaticDeploy
{
    const CONFIG_CONST = 'SIMPLY_STATIC_DEPLOY_CONFIG';

    private $basePath;
    private $baseUrl;
    private $version;

    public function __construct(
        string $basePath,
        string $baseUrl,
        string $version
    ) {
        $this->basePath = $basePath;
        $this->baseUrl = $baseUrl;
        $this->version = $version;
    }

    public function init()
    {
        add_action('init', [$this, 'plugins_loaded']);
    }

    public function plugins_loaded()
    {
        $requirements = new DependencyList();
        $requirements->add_dependency(
            new Dependencies\SimplyStaticDependency()
        );
        $requirements->add_dependency(
            new Dependencies\ConfigDependency(self::CONFIG_CONST)
        );
        $requirements->add_dependency(
            new Dependencies\ConfigStructureDependency(self::CONFIG_CONST)
        );

        if (!$requirements->are_met()) {
            return;
        }

        $config = new Config(constant(self::CONFIG_CONST));

        // Bootstrap components.
        (new Admin(
            $config,
            $this->basePath,
            $this->baseUrl,
            $this->version
        ))->register();
        (new Api($config))->register();
    }
}
