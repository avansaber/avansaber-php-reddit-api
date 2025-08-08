<?php

declare(strict_types=1);

namespace Avansaber\RedditApi\Http;

interface SleeperInterface
{
    public function sleep(int $milliseconds): void;
}

