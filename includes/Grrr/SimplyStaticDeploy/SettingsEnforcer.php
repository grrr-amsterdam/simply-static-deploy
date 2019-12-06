<?php namespace Grrr\SimplyStaticDeploy\SimplyStatic;

use Simply_Static;
use Garp\Functional as f;

class Settings {

    public static function enforce() {
        $url = f\prop('url', AWS_SITE);
        $host = preg_replace('(^https?://)', '', $url);

        $outputPath = rtrim(ABSPATH, '/') . '/../app/static/';
        if (!file_exists($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        $tempPath = rtrim(ABSPATH, '/') . '/../app/static-temp/';
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        Simply_Static\Options::instance()
            ->set('destination_url_type', 'absolute')
            ->set('destination_scheme', 'https://')
            ->set('destination_host', rtrim($host, '/'))
            ->set('delivery_method', 'local')
            ->set('local_dir', rtrim(realpath($outputPath), '/') . '/')
            ->set('temp_files_dir', rtrim(realpath($tempPath), '/') . '/')
            ->set('delete_temp_files', '1')
            ->set('additional_urls',
                rtrim(get_home_url(), '/') . '/sitemap.xml' . PHP_EOL
            )
            ->set('additional_files',
                rtrim(get_template_directory(), '/') . '/assets/build/' . PHP_EOL
            )
            ->set('urls_to_exclude', [
                [
                    'url' => '/app/uploads',
                    'do_not_save' => '1',
                    'do_not_follow' => '1',
                ],
            ])
            ->save();
    }

}
