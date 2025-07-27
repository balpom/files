<?php

declare(strict_types=1);

namespace Balpom\Files;

class Writer extends Handler
{
    protected $directoriesCreateTries = 50; //The number of attempts to create nested directories for a file (with an interval of ~100 milliseconds).
    protected $openTries = 50; //The number of attempts to open file for writing (with an interval of ~100 milliseconds).
    protected $lockTries = 50; //The number of attempts to obtain an exceptional lock (LOCK_EX) before writing a file (with an interval of ~100 milliseconds).
    protected $writeTries = 50; //The number of attempts to write a file (with an interval of ~100 milliseconds).

    public function write(string $content): void
    {
        $path = dirname($this->filePath);
        $this->createDirectoriesTree($path);

        $counter = 0;
        while ($counter < $this->openTries) {
            if ($this->resource = @fopen($this->filePath, 'w')) {
                break;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }

        if (!is_resource($this->resource)) {
            throw new WriterException('Unable to open resource for file ' . $this->filePath);
        }

        if (!$this->exclusiveLock()) {
            throw new WriterException('Unable to get exclusive lock for file ' . $this->filePath);
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

        if (!$result) {
            throw new WriterException('Unable to write content for file ' . $this->filePath);
        }

        return;
    }

    private function createDirectoriesTree(string $path): void
    {
        $counter = 0;
        while ($counter < $this->directoriesCreateTries) {
            if (!@is_dir($path)) {
                @mkdir($path, 0777, true); // mkdir ($path, 0777, TRUE) - recursively creating nested directories.
            }
            @clearstatcache(); // The results of the is_dir function are cached!
            if (@is_dir($path)) {
                return;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }
        if (!@is_dir($path)) {
            throw new WriterException('Unable to create directories tree for a path: ' . $path);
        }

        return;
    }

}
