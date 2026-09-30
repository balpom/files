<?php

declare(strict_types=1);

namespace Balpom\Files;

interface PathInterface
{
    /*
     * Set file or directory name (absolute path) and UMASK.
     */
    public function set(string $absolutePath, int $umask = 0022): PathInterface;

    /*
     * Check file or directory existence.
     */
    public function exists(): bool;

}