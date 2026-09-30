<?php

declare(strict_types=1);

namespace Balpom\Files;

interface DirectoryInterface extends PathInterface
{
    /*
     * Create directory.
     */
    public function create(): bool;

    /*
     * Delete empty directory. If $withAllSubdirectories = true,
     * all subdirectories with all files also will be deleted.
     */
    public function delete(bool $withAllSubdirectories = false): bool;

    /*
     * Delete directory and all empty subdirectories.
     * If $withNullSizedFiles = true, all files with zero file size
     * will be deleted (even if corresponding directory will not be deleted).
     */
    public function clean(bool $withZeroSizedFiles = false): bool;

    /*
     * Return TRUE, in directory not contains any files or subdirectories.
     */
    public function empty(): bool;

}