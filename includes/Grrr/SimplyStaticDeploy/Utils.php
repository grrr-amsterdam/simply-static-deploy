<?php namespace Grrr\SimplyStaticDeploy\Utils;

use Grrr\Utils\Assets;

/**
 * Render a template/partial.
 */
function partial(string $file, array $args = []) {
    (new Renderer($file, $args))->render();
}
