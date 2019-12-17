<?php namespace Grrr\SimplyStaticDeploy;

use Simply_Static;
use Garp\Functional as f;

class Utils {

    /**
     * Convert Simply Static option string to array.
     */
    public static function option_string_to_array(string $option): array {
        return Simply_Static\Util::string_to_array($option);
    }

    /**
     * Convert array to Simply Static option.
     */
    public static function array_to_option_string(array $array): string {
        return f\join(PHP_EOL, $array);
    }

}
