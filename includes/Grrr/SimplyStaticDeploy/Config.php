<?php namespace Grrr\SimplyStaticDeploy;

use Garp\Functional as f;

class Config {

    private $data;
    private $requiredFields;

    public function __construct(array $data, array $requiredFields) {
        $this->validate($data, $requiredFields);
        $this->data = $data;
        $this->requiredFields = $requiredFields;
    }

    public function __get(string $key) {
        $out = $this->data[$key] ?? '';
        if (is_array($out)) {
            return new self($out, $this->requiredFields[$key] ?? []);
        }
        return $out;
    }

    private function validate(array $data, array $requiredFields, string $prefix = ''): void {
        $requiredNestedKeys = array_keys(f\filter('is_array', $requiredFields));
        $requiredKeys = f\concat(
            f\filter('is_string', $requiredFields),
            $requiredNestedKeys
        );

        $missingProps = f\reject(
            f\prop_of($data),
            $requiredKeys
        );
        if (count($missingProps)) {
            $namespacedProps = f\map(f\concat($prefix), $missingProps);
            throw new Exception(
                sprintf('Missing required option(s): %s', implode(', ', $namespacedProps))
            );
        }

        foreach ($requiredNestedKeys as $nestedKey) {
            $this->validate($data[$nestedKey] ?? [], $requiredFields[$nestedKey], "$nestedKey.");
        }
    }

}
