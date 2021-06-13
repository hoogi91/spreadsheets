<?php

namespace Hoogi91\Spreadsheets\Tests\Unit;

use ArrayAccess;

/**
 * Trait ArrayAssertTrait
 * @package Hoogi91\Spreadsheets\Tests\Unit
 */
trait ArrayAssertTrait
{

    /**
     * Asserts that an array has a specified subset.
     *
     * @param array|ArrayAccess $subset
     * @param array|ArrayAccess $array
     * @param array $notAllowedClassInstances
     * @param string $message
     */
    public static function assertArraySubsetWithoutClassInstances(
        $subset,
        $array,
        array $notAllowedClassInstances = [],
        string $message = ''
    ): void {
        array_walk_recursive(
            $array,
            static function (&$item) use ($notAllowedClassInstances) {
                if (is_object($item) === true) {
                    $objectInstanceNames = array_merge(
                        class_implements($item),
                        class_parents($item),
                        [get_class($item)]
                    );

                    $matches = array_intersect($notAllowedClassInstances, $objectInstanceNames);
                    if (count($matches) > 0) {
                        // unset this item
                        $item = null;
                    }
                }
            }
        );

        parent::assertArraySubset($subset, $array, false, $message);
    }
}
