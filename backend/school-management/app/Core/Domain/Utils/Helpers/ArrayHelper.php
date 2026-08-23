<?php

namespace App\Core\Domain\Utils\Helpers;

final class ArrayHelper
{

    public static function filterNullValues(array $values): array
    {
        return array_filter(
            $values,
            fn ($value) => $value !== null
        );
    }

    public static function mergeValues(array $values1, array $values2): array
    {
        return array_merge(
            $values1,
            $values2,
        );
    }

}
