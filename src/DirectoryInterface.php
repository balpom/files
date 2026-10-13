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
     * Return TRUE in directory, what not contains any files or subdirectories.
     */
    public function empty(): bool;

    /*
     * Scan directory on $level levels and return all files in it as "File" object.
     *
     * If $level = TRUE - scan all levels.
     *
     * IF $level = FALSE or $level < 1 - scan on one level.
     */
    public function files(int|bool $level = 1): array;

    /*
     * Scan directory on $level levels and return all directories in it as "Directory" object.
     *
     * If $level = TRUE - scan all levels.
     *
     * IF $level = FALSE or $level < 1 - scan on one level.
     */
    public function directories(int|bool $level = 1): array;

    /*
     * Scan directory on $level levels and return all files and directories in it
     * as "File" and "Directory" objects.
     *
     * If $level = TRUE - scan all levels.
     *
     * IF $level = FALSE or $level < 1 - scan on one level.
     */
    public function objects(int|bool $level = 1): array;

}