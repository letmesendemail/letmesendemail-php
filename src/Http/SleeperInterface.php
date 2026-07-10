<?php

declare(strict_types=1);

namespace LetMeSendEmail\Http;

interface SleeperInterface
{
    public function sleep(int $milliseconds): void;
}
