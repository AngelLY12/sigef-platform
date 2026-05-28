<?php

namespace App\Core\Domain\Enum\Cache;

enum ParentCacheSufix: string
{
    case CHILDREN = 'children';
    case PARENTS = 'parents';
}
