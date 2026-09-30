<?php

declare(strict_types=1);

namespace Balpom\Files;

interface FileInterface extends PathInterface
{
    /*
     * Get file content.
     */
    public function read(): string;

    /*
     * Write content to file.
     */
    public function write(string $content): bool;

    /*
     * Delete file. If $withEmptyDirectories = true,
     * all empty subdirectories also will be deleted.
     */
    public function delete(bool $withEmptyDirectories = false): bool;

}