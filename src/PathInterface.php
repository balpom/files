<?php

declare(strict_types=1);

namespace Balpom\Files;

interface PathInterface
{
    /*
     * Set base directory.
     * If base directory don't define - where is root directory is base directory
     * (file path in "set" method must be absolute).
     *
     * As sample:
     * if
     * $rootDirectory = "/" (or, as sample, "C:\" under Windows)
     * $baseDirectory = "/var/www/app/data/"
     * $absolutePath = "/subdir/datafile.bin"
     * then
     * full file path will be "/var/www/app/data/subdir/datafile.bin"
     * else
     * full file path will be "/subdir/datafile.bin"
     * (or, as sample, "C:\subdir/datafile.bin" under Windows)
     */
    public function base(string|null $baseDirectory = null): PathInterface;

    /*
     * Set file or directory name (path from base directory or absolute path,
     * if base directory not set) and UMASK.
     */
    public function set(string $path, int $umask = 0022): PathInterface;

    /*
     * Check file or directory existence.
     */
    public function exists(): bool;

}