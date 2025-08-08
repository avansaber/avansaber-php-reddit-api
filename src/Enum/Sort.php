<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Enum;

enum Sort: string
{
    case Relevance = 'relevance';
    case Hot = 'hot';
    case Top = 'top';
    case New = 'new';
    case Comments = 'comments';
}


