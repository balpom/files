<?php

declare(strict_types=1);

namespace Balpom\Files;

interface TimeInterface
{
    /*
     * Get file or directory creation time (Unix timestamp).
     */
    public function getTime(): int|false;

    /*
     * Set file or directory creation time (Unix timestamp).
     */
    public function setTime(int|null $time = null): bool;

}