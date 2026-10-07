<?php

declare(strict_types=1);

namespace Balpom\Files;

class Directory extends Handler implements DirectoryInterface
{
    protected $directoriesCreateTries = 10; //The number of attempts to create nested directories for a file (with an interval of ~100 milliseconds).
    protected $directoriesDeleteTries = 10; //The number of attempts to delete nested directories (with an interval of ~100 milliseconds).

    public function set(string $absolutePath, int $umask = 0022): PathInterface
    {
        umask($umask);
        $this->init($absolutePath);

        return $this;
    }

    /*
     * Create directory.
     */
    public function create(): bool
    {
        $counter = 0;
        while ($counter < $this->directoriesCreateTries) {
            @clearstatcache(true, $this->absolutePath); // The results of the is_dir function are cached!
            if (!@is_dir($this->absolutePath)) {
                @mkdir($this->absolutePath, 0777, true); // mkdir ($path, 0777, TRUE) - recursively creating nested directories.
            }
            @clearstatcache(true, $this->absolutePath);
            if (@is_dir($this->absolutePath)) {
                return true;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }

        return false;
    }

    /*
     * Delete empty directory. If $withAllSubdirectories = true,
     * all subdirectories with all files also will be deleted.
     */
    public function delete(bool $withAllSubdirectories = false): bool
    {
        try {
            $this->checkBaseDirectory($this->absolutePath);
            $this->checkDirectory($this->absolutePath);
        } catch (DirectoryException $e) {
            @clearstatcache(true, $this->absolutePath);
            return !@file_exists($this->absolutePath);
        }

        if (!$withAllSubdirectories) {
            $method = 'deleteDirectory';
        } else {
            $method = 'deleteDirectoryTree';
        }

        try {
            $result = $this->$method($this->absolutePath);
        } catch (DirectoryException $e) {
            @clearstatcache(true, $this->absolutePath);
            return !@file_exists($this->absolutePath);
        }

        return $result;
    }

    /*
     * Delete directory and all empty subdirectories.
     * If $withNullSizedFiles = true, all files with zero file size
     * will be deleted (even if corresponding directory will not be deleted).
     */
    public function clean(bool $withZeroSizedFiles = false): bool
    {
        try {
            $this->checkBaseDirectory($this->absolutePath);
            $this->checkDirectory($this->absolutePath);
        } catch (DirectoryException $e) {
            @clearstatcache(true, $this->absolutePath);
            return !@file_exists($this->absolutePath);
        }

        if (!$withZeroSizedFiles) {
            $method = 'cleanDirectoryTree';
        } else {
            $method = 'deepCleanDirectoryTree';
        }

        try {
            $result = $this->$method($this->absolutePath);
        } catch (DirectoryException $e) {
            @clearstatcache(true, $this->absolutePath);
            return !file_exists($this->absolutePath);
        }

        return $result;
    }

    /*
     * Return TRUE in directory what not contains any files or subdirectories.
     */
    public function empty(): bool
    {
        return $this->isDirectoryEmpty($this->absolutePath);
    }

    protected function isDirectoryEmpty(string $dir): bool
    {
        $this->checkDirectory($dir);
        $files = $this->getDirectoryContent($dir);

        return 0 === count($files) ? true : false;
    }

    protected function checkBaseDirectory(string $dir): void
    {
        if ('' !== $this->baseDirectory) {
            $baseDirectoryLen = strlen($this->baseDirectory);
            $absolutePathSubstr = substr($dir, 0, $baseDirectoryLen);
            if ($this->baseDirectory !== $absolutePathSubstr) {
                throw new HandlerException('Base directory, if it is not empty, must be first part of absolute directory path.');
            }
        }
    }

    protected function checkDirectory(string $dir): void
    {
        @clearstatcache(true, $dir);
        if (!file_exists($dir)) {
            throw new DirectoryException('Not existing directory: ' . $dir);
        }
        if (!is_dir($dir)) {
            throw new DirectoryException('It is not a directory: ' . $dir);
        }
    }

    protected function getDirectoryContent(string $dir): array
    {
        $files = @scandir($dir);
        if (false === $files) {
            throw new DirectoryException('Unable to scan directory: ' . $dir);
        }

        return array_diff($files, ['.', '..']);
    }

    protected function deleteDirectory(string $dir): bool
    {
        $counter = 0;
        while ($counter < $this->directoriesDeleteTries) {
            @clearstatcache(true, $dir);
            if (!@file_exists($dir)) {
                return true;
            }

            $files = $this->getDirectoryContent($dir);
            if (0 !== count($files)) {
                usleep(mt_rand(5000, 10000));
                $counter++;
                continue;
            }
            if (@rmdir($dir)) {
                return true;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }

        return false;
    }

    protected function deleteDirectoryTree(string $dir): bool
    {
        $files = $this->getDirectoryContent($dir);
        $totalResult = true;
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            // true && true = TRUE; true && false = FALSE;
            @clearstatcache(true, $dir);
            if (is_dir($path)) {
                $result = $this->deleteDirectoryTree($path);
                $totalResult = $totalResult && $result;
            } else {
                $result = (new File($path))->delete();
                $totalResult = $totalResult && $result;
            }
        }
        $result = (new Directory($dir))->delete();
        $totalResult = $totalResult && $result;

        return $totalResult;
    }

    protected function cleanDirectoryTree(string $dir): bool
    {
        $files = $this->getDirectoryContent($dir);
        $totalResult = true;
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            // true && true = TRUE; true && false = FALSE;
            @clearstatcache(true, $dir);
            if (is_dir($path)) {
                if ($this->isDirectoryEmpty($path)) {
                    $result = (new Directory($path))->delete();
                    $totalResult = $totalResult && $result;
                } else {
                    $result = $this->cleanDirectoryTree($path);
                    $totalResult = $totalResult && $result;
                }
            } else {
                $result = false;
            }
        }
        if ($this->isDirectoryEmpty($dir)) {
            $result = (new Directory($dir))->delete();
            $totalResult = $totalResult && $result;
        }

        return $totalResult;
    }

    protected function deepCleanDirectoryTree(string $dir): bool
    {
        $files = $this->getDirectoryContent($dir);
        $totalResult = true;
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            // true && true = TRUE; true && false = FALSE;
            @clearstatcache(true, $dir);
            if (is_dir($path)) {
                if ($this->isDirectoryEmpty($path)) {
                    $result = (new Directory($path))->delete();
                    $totalResult = $totalResult && $result;
                } else {
                    $result = $this->deepCleanDirectoryTree($path);
                    $totalResult = $totalResult && $result;
                }
            } else {
                @clearstatcache(true, $dir);
                if (0 === filesize($path)) {
                    $result = (new File($path))->delete();
                    $totalResult = $totalResult && $result;
                }
            }
        }
        if ($this->isDirectoryEmpty($dir)) {
            $result = (new Directory($dir))->delete();
            $totalResult = $totalResult && $result;
        }

        return $totalResult;
    }

}