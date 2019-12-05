<?php namespace Grrr\SimplyStaticDeploy\Utils;

class Renderer {

    private $file;
    private $args;

    public function __get($name) {
        if (isset($this->args[$name])) {
            return $this->args[$name];
        }
    }

    public function __isset($name): bool {
        return isset($this->args[$name]);
    }

    public function __construct(string $file, array $args = []) {
        if (strpos($file, '.php') === false) {
            $file .= '.php';
        }
        $this->file = $file;
        $this->args = $args;
    }

    public function render() {
        switch (true):
            case locate_template($this->file):
                include(locate_template($this->file));
                break;
            case locate_template('templates/' . $this->file):
                include(locate_template('templates/' . $this->file));
                break;
            default:
                include($this->file);
        endswitch;
    }

    public function get(): string {
        ob_start();
        self::render();
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

}
