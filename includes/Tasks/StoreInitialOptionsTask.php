<?php

namespace Grrr\SimplyStaticDeploy\Tasks;

use Garp\Functional as f;
use Grrr\SimplyStaticDeploy\Utils;
use Simply_Static\Options;
use Simply_Static\Plugin;
use Simply_Static\Task;
use Simply_Static\Util;

class StoreInitialOptionsTask extends Task
{
    protected static $task_name = 'store_initial_options';

    const FILES_FILTER = 'simply_static_deploy_additional_files';
    const URLS_FILTER = 'simply_static_deploy_additional_urls';

    public function perform()
    {
        // get initial options
        // store them somewhere in the database
        $optionsToStore = F\pick(
            ['additional_files', 'additional_urls', 'urls_to_exclude'],
            $this->options->get_as_array()
        );

        $this->save_status_message('Store initial options');
        Util::debug_log('Store initial options');
        update_option(Plugin::SLUG . '_tmp_options', $optionsToStore);

        $this->save_status_message('Enrich files and urls stack');
        $initialAdditionalFilesArray = Utils::option_string_to_array(
            $this->options->get('additional_files')
        );
        $initialAdditionalUrlsArray = Utils::option_string_to_array(
            $this->options->get('additional_urls')
        );

        Util::debug_log(
            count($initialAdditionalFilesArray) . ' additonal files, initially'
        );
        Util::debug_log(
            count($initialAdditionalUrlsArray) . ' additonal urls, initially'
        );

        // Alter options
        $files = has_filter(static::FILES_FILTER)
            ? apply_filters(static::FILES_FILTER, $initialAdditionalFilesArray)
            : $this->resolve_additional_files($initialAdditionalFilesArray);
        $urls = has_filter(static::URLS_FILTER)
            ? apply_filters(static::URLS_FILTER, $initialAdditionalUrlsArray)
            : $this->enrich_additional_urls($initialAdditionalUrlsArray);

        Util::debug_log(count($files) . ' additonal files, after enriching');
        Util::debug_log(count($urls) . ' additonal urls, after enriching');

        $this->options
            ->set('additional_files', Utils::array_to_option_string($files))
            ->set('additional_urls', Utils::array_to_option_string($urls))
            ->save();

        return true;
    }

    /**
     * Resolve all symbolic links to absolute/real paths, because we call
     * the plugin via the REST API. Since we have installed the site in a
     * subdirectory (`/wp`) the `get_home_path()` function returns nothing.
     * See: https://wordpress.stackexchange.com/a/188461
     *
     * On the server our site is installed via a symlinked release. Since our
     * `additional_files` setting in the CMS needs to be persistent, we set it
     * to the `/current` path. The WordPress constants in the plugin return the
     * absolute paths (`/release/2893479823/`) resulting in a mismatch in the plugin.
     *
     * We're fixing this by temporarily resolving all `additional_files` paths
     * to absolute URLs with resolved symlinks, and restoring them after completion.
     */
    private function resolve_additional_files(array $files): array
    {
        return f\unique(f\map('realpath', $files));
    }

    /**
     * Pages or posts which aren't linked to and which are unavailable in the
     * sitemap will not be fetched, since Simply Static can't find them.
     * We'll have to fetch them manually and append them to the `additional_urls`
     * option during the archive tasks.
     */
    private function enrich_additional_urls(array $urls): array
    {
        return f\unique(
            f\concat(
                $urls,
                $this->fetch_password_protected_posts(),
                $this->fetch_yoast_noindex_posts()
            )
        );
    }

    /**
     * Fetch password protected posts.
     */
    private function fetch_password_protected_posts(): array
    {
        $query = new \WP_Query([
            'post_type' => 'any',
            'has_password' => true,
            'posts_per_page' => -1,
        ]);
        return f\map(f\compose('get_permalink', f\prop('ID')), $query->posts);
    }

    /**
     * Fetch posts which are set to `noindex` by Yoast SEO.
     */
    private function fetch_yoast_noindex_posts(): array
    {
        $query = new \WP_Query([
            'post_type' => 'any',
            'meta_key' => '_yoast_wpseo_meta-robots-noindex',
            'meta_value_num' => 1,
            'posts_per_page' => -1,
        ]);
        return f\map(f\compose('get_permalink', f\prop('ID')), $query->posts);
    }
}
