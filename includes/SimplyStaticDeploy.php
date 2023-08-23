<?php namespace Grrr\SimplyStaticDeploy;

use Garp\Functional as f;
use Grrr\SimplyStaticDeploy\Aws\ClientProvider;
use Grrr\SimplyStaticDeploy\Aws\CloudFront\Invalidation;
use WP_Post;

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

        add_action(
            'transition_post_status',
            [$this, 'remove_static_pages_for_certain_status_transitions'],
            10,
            3
        );

        add_action(
            'post_updated',
            [$this, 'remove_old_static_page_when_slug_is_changed'],
            10,
            3
        );
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

    /**
     * Removes static pages based on post status transitions.
     * [Post Status Transitions](https://codex.wordpress.org/Post_Status_Transitions)
     *
     * @param string $new_status
     * @param string $old_status
     * @param \WP_Post $post
     * @return void
     */
    public function remove_static_pages_for_certain_status_transitions(
        string $new_status,
        string $old_status,
        \WP_Post $post
    ) {
        if ($new_status === $old_status) {
            return;
        }

        if (!$this->is_post_applicable($post)) {
            return;
        }

        if ($this->isTransitionForRemoval($new_status, $old_status)) {
            $this->remove_static_page($post);
        }
    }

    public function remove_old_static_page_when_slug_is_changed(
        int $post_ID,
        WP_Post $post_after,
        WP_Post $post_before
    ) {
        if ($post_after->post_name === $post_before->post_name) {
            return;
        }

        if (!$this->is_post_applicable($post_after)) {
            return;
        }

        $this->remove_static_page($post_before);
    }

    /**
     * Decide whether status transition should trigger removal of static page.
     *
     * @param string $new_status
     * @param string $old_status
     * @return bool
     */
    private function isTransitionForRemoval(
        string $new_status,
        string $old_status
    ) {
        $statuses404 = ['pending', 'draft', 'private', 'trash', 'future'];
        $statuses200 = ['publish', 'inherit'];

        // Return true if new status is in statuses404 and old status is in statuses200.
        return in_array($new_status, $statuses404) &&
            in_array($old_status, $statuses200);
    }

    protected function remove_static_page(\WP_Post $post)
    {
        // Setup AWS S3 client with Simply Static Deploy code.
        $config = new Config(constant(self::CONFIG_CONST));
        $clientProvider = new ClientProvider($config->aws);
        $s3client = $clientProvider->getS3Client();

        $permalink = $this->get_pretty_permalink($post);
        $relativePermalink = wp_make_link_relative($permalink);

        // Remove the object.
        $s3Key = ltrim($relativePermalink, '/') . 'index.html';

        // Safety precaution: Never remove the homepage
        // @TODO: Check why this happened one time. It should not be possible.
        if ($s3Key === 'index.html') {
            return;
        }

        var_dump('BB');
        $s3client->deleteObject([
            'Bucket' => $config->aws->bucket,
            'Key' => $s3Key,
        ]);

    }

    /**
     * When post status is not private or publish, the permalink is not 'pretty' anymore,
     * instead it returns a link composed only with query parameters,
     * for example: http://localhost.theoceancleanup.com/?p=9686
     * In this case, we need to construct this pretty permalink ourself.
     *
     * We use `get_sample_permalink` for this
     * https://developer.wordpress.org/reference/functions/get_sample_permalink/
     *
     * @param \WP_Post $post
     *
     * @return string
     */
    protected function get_pretty_permalink(\WP_Post $post): string
    {
        if (
            $post->post_status === 'private' ||
            $post->post_status == 'publish'
        ) {
            return get_permalink($post);
        }
        $samplePermalink = get_sample_permalink($post);

        return str_replace(
            ["%postname%", "%pagename%"],
            $samplePermalink[1], // The post name
            $samplePermalink[0] // https://example.com/updates/%postname%/
        );
    }

    private function is_post_applicable(\WP_Post $post): bool
    {
        $publicPostTypes = get_post_types(['public' => true]);
        $postTypesToObserve = f\omit(['attachment'], $publicPostTypes);

        return in_array($post->post_type, $postTypesToObserve);
    }
}
