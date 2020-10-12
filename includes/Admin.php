<?php namespace Grrr\SimplyStaticDeploy;

use Garp\Functional as f;
use Grrr\SimplyStaticDeploy\Utils\Renderer;
use Simply_Static\Options;
use WP_Post;

class Admin
{
    const SLUG = 'simply-static-deploy';
    const JS_GLOBAL = 'SIMPLY_STATIC_DEPLOY';

    const DEPLOY_FORM_ID = 'ssd-single-deploy-form';

    private $basePath;
    private $baseUrl;
    private $version;
    private $config;

    public function __construct(
        Config $config,
        string $basePath,
        string $baseUrl,
        string $version
    ) {
        $this->config = $config;
        $this->basePath = $basePath;
        $this->baseUrl = $baseUrl;
        $this->version = $version;
    }

    public function register()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_before_admin_bar_render', [$this, 'admin_bar']);


        add_action('post_submitbox_misc_actions', [$this, 'post_submitbox_misc_actions']);
        add_action('admin_footer', [$this, 'render_deploy_single_form']);
    }

    public function admin_bar()
    {
        global $wp_admin_bar;
        $wp_admin_bar->add_node([
            'id' => static::SLUG,
            'title' => 'Deploy',
            'href' => admin_url() . 'admin.php?page=' . static::SLUG,
        ]);
    }

    public function admin_menu()
    {
        add_menu_page(
            'Deploy Website',
            'Deploy',
            'edit_posts',
            static::SLUG,
            [$this, 'render_admin'],
            $this->get_icon('rocket.svg')
        );
    }

    public function register_assets()
    {
        wp_register_style(
            static::SLUG,
            $this->get_asset_url('admin.css'),
            [],
            $this->version
        );
        wp_register_script(
            static::SLUG,
            $this->get_asset_url('admin.js'),
            ['jquery'],
            $this->version
        );
        wp_localize_script(static::SLUG, static::JS_GLOBAL, [
            'api' => [
                'nonce' => wp_create_nonce('wp_rest'),
                'endpoints' => $this->get_endpoints(),
            ],
            'tasks' => $this->get_tasks(),
            'website' => trim($this->config->url, '/') . '/',
        ]);
    }

    public function render_admin()
    {
        wp_enqueue_script(static::SLUG);
        wp_enqueue_style(static::SLUG);

        $form = (object)[
            'action' => $this->get_endpoints()['simply_static_deploy'],
            'method' => 'post',
        ];

        $renderer = new Renderer($this->basePath . 'views/admin-page.php', [
            'form' => $form,
            'in_progress' => !StaticDeployJob::is_job_done(),
            'last_end_time' => StaticDeployJob::last_end_time(),
        ]);
        $renderer->render();
    }

    public function post_submitbox_misc_actions($post) {
        if (!$this->eligible_for_single_deploy($post)) {
            return;
        }
        $is_job_done = StaticDeployJob::is_job_done();
        $renderer = new Renderer($this->basePath . 'views/post-submit-actions.php', [
            'form_id' => static::DEPLOY_FORM_ID,
            'status' => $is_job_done ? 'ready' : 'busy',
            'status_message' => $is_job_done ? 'Ready for deployment' : 'Deployment in progress..',
            'poll_status_endpoint' => $this->get_endpoints()['poll_status'],
        ]);
        $renderer->render();
    }

    public function render_deploy_single_form() {
        $screen = get_current_screen();
        if (!($screen->base === 'post' && $screen->parent_base === 'edit')) {
            return;
        }

        $post = get_post();
        if (!$this->eligible_for_single_deploy($post)) {
            return;
        };
        $form = (object) [
            'id' => static::DEPLOY_FORM_ID,
            'action' => $this->get_endpoints()['generate_single'],
            'method' => 'POST',
        ];
        $renderer = new Renderer($this->basePath . 'views/deploy-single-form.php', [
            'form' => $form,
            'post' => $post,
        ]);
        $renderer->render();
    }

    public function eligible_for_single_deploy(WP_Post $post) {
        $post_type_object = get_post_type_object($post->post_type);
        return $post_type_object->public;
    }

    private function get_tasks(): array
    {
        $tasks = ['generate', 'sync'];
        if ($this->config->aws->distribution) {
            $tasks[] = 'invalidate';
        }
        return $tasks;
    }

    private function get_times(): array
    {
        $times = [
            'generate' => $this->get_last_time(Generator::get_last_time()),
            'sync' => $this->get_last_time(Syncer::get_last_time()),
        ];
        if ($this->config->aws->distribution) {
            $times['invalidate'] = $this->get_last_time(
                Invalidator::get_last_time()
            );
        }
        return $times;
    }

    private function get_last_time($timestamp): string
    {
        $tz = get_option('timezone_string') ?: date_default_timezone_get();
        date_default_timezone_set($tz);
        return $timestamp ? date_i18n('j F H:i', $timestamp) : '';
    }

    private function get_asset_url(string $filename): string
    {
        return rtrim($this->baseUrl, '/') . '/assets/' . $filename;
        $relative_assets_dir =
            substr($this->basePath, strlen(get_theme_file_path())) . '/assets';
        return get_theme_file_uri($relative_assets_dir . '/' . $filename);
    }

    private function get_icon(string $filename): string
    {
        $icon = $this->basePath . 'assets/' . $filename;
        return 'data:image/svg+xml;base64,' .
            base64_encode(file_get_contents($icon));
    }

    private function get_endpoints()
    {
        $out = [];
        foreach (Api::ENDPOINT_MAPPER as $endpoint => $callback) {
            $out[$endpoint] = RestRoutes::url($endpoint);
        }
        return $out;
    }

}
