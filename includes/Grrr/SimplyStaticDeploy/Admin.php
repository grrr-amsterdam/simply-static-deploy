<?php namespace Grrr\SimplyStaticDeploy;

use Garp\Functional as f;
use Grrr\SimplyStaticDeploy\Utils\Renderer;

class Admin {

    const SLUG = 'grrr-simply-static-deploy';
    const JS_GLOBAL = 'SIMPLY_STATIC_DEPLOY';

    private $basePath;
    private $baseUrl;
    private $version;
    private $config;

    public function __construct(
        Config $config, string $basePath, string $baseUrl, string $version
    ) {
        $this->config = $config;
        $this->basePath = $basePath;
        $this->baseUrl = $baseUrl;
        $this->version = $version;
    }

    public function register() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_before_admin_bar_render', [$this, 'admin_bar']);
    }

    public function admin_bar() {
        global $wp_admin_bar;
        $wp_admin_bar->add_node([
            'id' => static::SLUG,
            'title' => 'Deploy',
            'href' => admin_url() . 'admin.php?page=' . static::SLUG,
        ]);
    }

    public function admin_menu() {
        add_menu_page(
            'Deploy Website',
            'Deploy',
            'edit_posts',
            static::SLUG,
            [$this, 'render_admin'],
            $this->get_icon('rocket.svg')
        );
    }

    public function register_assets() {
        wp_register_style(static::SLUG, $this->get_asset_url('admin.css'), [], $this->version);
        wp_register_script(static::SLUG, $this->get_asset_url('admin.js'), ['jquery'], $this->version);
        wp_localize_script(static::SLUG, static::JS_GLOBAL, [
            'api' => [
                'nonce' => wp_create_nonce('wp_rest'),
                'endpoints' => $this->get_endpoints(),
            ],
            'tasks' => $this->get_tasks(),
            'website' => trim($this->config->url, '/') . '/',
        ]);
    }

    public function render_admin() {
        wp_enqueue_script(static::SLUG);
        wp_enqueue_style(static::SLUG);
        $renderer = new Renderer(
            $this->basePath . 'views/admin-page.php',
            [
                'endpoints' => $this->get_endpoints(),
                'tasks' => $this->get_tasks(),
                'times' => $this->get_times(),
                'in_progress' => Archive::is_in_progress(),
            ]
        );
        $renderer->render();
    }

    private function get_tasks(): array {
        $tasks = ['generate', 'sync'];
        if ($this->config->aws->distribution) {
            $tasks[] = 'invalidate';
        }
        return $tasks;
    }

    private function get_times(): array {
        $times = [
            'generate' => $this->get_last_time(Generator::get_last_time()),
            'sync' => $this->get_last_time(Syncer::get_last_time()),
        ];
        if ($this->config->aws->distribution) {
            $times['invalidate'] = $this->get_last_time(Invalidator::get_last_time());
        }
        return $times;
    }

    private function get_last_time($timestamp): string {
        $tz = get_option('timezone_string') ?: date_default_timezone_get();
        date_default_timezone_set($tz);
        return $timestamp
            ? date_i18n('j F H:i', $timestamp)
            : '';
    }

    private function get_asset_url(string $filename): string {
        return rtrim($this->baseUrl, '/') . '/assets/' . $filename;
        $relative_assets_dir = substr($this->basePath, strlen(get_theme_file_path())) . '/assets';
        return get_theme_file_uri($relative_assets_dir . '/' . $filename);
    }

    private function get_icon(string $filename): string {
        $icon = $this->basePath . 'assets/' .$filename;
        return 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($icon));
    }

    private function get_endpoints() {
        $out = [];
        foreach (Api::ENDPOINT_MAPPER as $endpoint => $callback) {
            $out[$endpoint] = RestRoutes::url($endpoint);
        }
        return $out;
    }

}
