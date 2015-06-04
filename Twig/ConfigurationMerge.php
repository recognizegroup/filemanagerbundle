<?php
namespace Recognize\FilemanagerBundle\Twig;

/**
 * Merges configurations together so that nested values from the second array overwrite the values of the first array
 *
 * Class ConfigurationMerge
 * @package Recognize\FilemanagerBundle\Twig
 */
class ConfigurationMerge {

    public function __invoke( &$array1, &$array2 ){
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->__invoke($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        return $merged;
    }

}