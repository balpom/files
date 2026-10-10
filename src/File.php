<?php

declare(strict_types=1);

namespace Balpom\Files;

class File extends Handler implements FileInterface
{
    protected $openForReadingTries = 20; //The number of attempts to open file for reading (with an interval of ~100 milliseconds).
    protected $lockForReadingTries = 20; //The number of attempts to obtain a shared lock (LOCK_SH) before reading a file (with an interval of ~100 milliseconds).
    protected $readTries = 20; //The number of attempts to read a file (with an interval of ~100 milliseconds).
    protected $openForWritingTries = 30; //The number of attempts to open file for writing (with an interval of ~100 milliseconds).
    protected $lockForWritingTries = 30; //The number of attempts to obtain an exceptional lock (LOCK_EX) before writing a file (with an interval of ~100 milliseconds).
    protected $writeTries = 30; //The number of attempts to write a file (with an interval of ~100 milliseconds).
    protected $fileDeleteTries = 40; // //The number of attempts to delete a file (with an interval of ~100 milliseconds).
    protected mixed $resource = null;

    public function set(string $path, int $umask = 0022): PathInterface
    {
        $this->close();
        umask($umask);
        $this->init($path);

        return $this;
    }

    /*
     * Get file content.
     */
    public function read(): string
    {
        @clearstatcache(true, $this->absolutePath);
        if (!@file_exists($this->absolutePath)) {
            throw new FileReadException('File not exists: ' . $this->absolutePath);
        }
        if (is_dir($this->absolutePath)) {
            throw new FileReadException('It is not a file: ' . $this->absolutePath . ' It is a directory!');
        }

        $content = false;

        // Open for reading in binary mode
        // (reading will not be interrupted at zero characters (ASCII 0)).
        $counter = 0;
        while ($counter < $this->openForReadingTries) {
            if ($this->resource = @fopen($this->absolutePath, 'rb')) {
                break;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }

        if (!is_resource($this->resource)) {
            throw new FileReadException('Unable to open resource for file ' . $this->absolutePath);
        }

        if (!$this->sharedLock($this->lockForReadingTries)) {
            throw new FileReadException('Unable to get shared lock for file ' . $this->absolutePath);
        }

        $counter = 0;
        @clearstatcache(true, $this->absolutePath);
        $fileSize = @filesize($this->absolutePath);
        if (0 !== $fileSize) {
            while ($counter < $this->readTries) {
                $content = @fread($this->resource, $fileSize);
                if (false !== $content) {
                    break;
                }
                $counter++;
                usleep(mt_rand(70000, 130000));
            }
        } else {
            $content = '';
        }

        $this->close();

        if (false === $content) {
            throw new FileReadException('Unable to read file ' . $this->absolutePath);
        }

        return $content;
    }

    /*
     * Write content to file.
     */
    public function write(string $content): bool
    {
        @clearstatcache(true, $this->absolutePath); // The results of the is_dir function are cached!
        if (is_dir($this->absolutePath)) {
            throw new FileWriteException('Path ' . $this->absolutePath . ' already exists and it is a directory!');
        }

        $path = dirname($this->absolutePath);
        if (!((new Directory($path))->create())) {
            return false;
        }

        $counter = 0;
        while ($counter < $this->openForWritingTries) {
            if ($this->resource = @fopen($this->absolutePath, 'w')) {
                break;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }

        if (!is_resource($this->resource)) {
            throw new FileWriteException('Unable to open resource for file ' . $this->absolutePath);
        }

        if (!$this->exclusiveLock($this->lockForWritingTries)) {
            throw new FileWriteException('Unable to get exclusive lock for file ' . $this->absolutePath);
        }

        @set_file_buffer($this->resource, 0); // Setted zero buffer so that it is immediately written directly to the file.
        $counter = 0;
        while ($counter < $this->writeTries) {
            $result = @fwrite($this->resource, $content);
            if (false !== $result) {
                break;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }

        $this->close();

        if (false === $result) {
            throw new FileWriteException('Unable to write content for file ' . $this->absolutePath);
        }

        return !empty($result);
    }

    /*
     * Delete file. If $withEmptyDirectories = true,
     * all empty subdirectories under it, but not higher
     * $this->baseDirectory (if it is not empty) also will be deleted.
     */
    public function delete(bool $withEmptyDirectories = false): bool
    {
        $result = $this->deleteFile();
        if (!$withEmptyDirectories) {
            return $result;
        }

        if ($result) {
            $result = $this->deleteEmptyDirectories();
        }

        return $result;
    }

    protected function deleteFile(): bool
    {
        @clearstatcache(true, $this->absolutePath);
        if (is_dir($this->absolutePath)) {
            throw new FileDeleteException('It is not a file: ' . $this->absolutePath . ' It is a directory!');
        }

        $counter = 0;
        while ($counter < $this->fileDeleteTries) {
            @clearstatcache(true, $this->absolutePath);
            if (!@file_exists($this->absolutePath) || @unlink($this->absolutePath)) {
                return true;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }

        return false;
    }

    /*
     * Delete all empty directories under $this->absolutePath,
     * but not higher $this->baseDirectory (if it is not empty).
     */
    protected function deleteEmptyDirectories(): bool
    {
        $dir = pathinfo($this->absolutePath, PATHINFO_DIRNAME) . '/';
        $totalResult = true;
        $baseDirectory = ('' === $this->baseDirectory) ? $this->rootDirectory : $this->baseDirectory;
        while (1 < substr_count($dir, '/') && $dir !== $baseDirectory) {
            try {
                $directory = new Directory($dir);
                if (!$directory->empty($dir)) {
                    return false;
                }
                // true && true = TRUE; true && false = FALSE;
                $result = $directory->delete();
                $totalResult = $totalResult && $result;
            } catch (DirectoryException $e) {
                // Not doing anything, because $dir may be not existing directory.
            }
            $dir = pathinfo($dir, PATHINFO_DIRNAME) . '/';
        }

        @clearstatcache(true, $dir);
        if (!file_exists($dir)) {
            return true;
        }

        return $totalResult;
    }

    protected function checkPath(string $path): void
    {
        $baseName = pathinfo($path, PATHINFO_BASENAME,);
        if (255 < strlen($baseName)) {
            throw new FileException('File name lenght is out of range!');
        }
    }

    protected function sharedLock(int $tries = 1): bool
    {
        $tries = 0 < $tries ? $tries : 1;
        return $this->lock($this->resource, LOCK_SH | LOCK_NB, $tries);
    }

    protected function exclusiveLock(int $tries = 1): bool
    {
        $tries = 0 < $tries ? $tries : 1;
        return $this->lock($this->resource, LOCK_EX | LOCK_NB, $tries);
    }

    protected function unlock(): void
    {
        @flock($this->resource, LOCK_UN);
    }

    private function lock(mixed $fh, int $lockFlag, int $tries = 1): bool
    {
        if (!is_resource($fh)) {
            throw new FileException('Bad argument for lock method.');
        }

        $locked = false;
        while (0 < $tries or !$locked) {
            $locked = @flock($fh, $lockFlag);
            if ($locked) {
                return true; // If the lock is setted, immediately returning TRUE.
            }
            if (1 < $tries) { // If only one attempt is given, then there is no need to wait if it was unsuccessful.
                usleep(mt_rand(70000, 130000));
            }
            $tries--;
        }

        return false;
    }

    protected function close(): void
    {
        if (is_resource($this->resource)) {
            @fflush($this->resource);
            $this->unlock();
            @fclose($this->resource);
            @clearstatcache();
        }
    }

    public function __destruct()
    {
        $this->close(); // Just in case...
    }

}