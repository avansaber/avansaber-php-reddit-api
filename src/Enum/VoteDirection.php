<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Enum;

enum VoteDirection: int
{
    case Down = -1;
    case None = 0;
    case Up = 1;
}


