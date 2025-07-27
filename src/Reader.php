<?php

declare(strict_types=1);

namespace Balpom\Files;

class Reader extends Handler
{
    protected $openTries = 20; //The number of attempts to open file for reading (with an interval of ~100 milliseconds).
    protected $lockTries = 20; //The number of attempts to obtain a shared lock (LOCK_SH) before reading a file (with an interval of ~100 milliseconds).
    protected $readTries = 20; //The number of attempts to read a file (with an interval of ~100 milliseconds).

    public function read(): string
    {
        if (!file_exists($this->filePath)) {
            throw new ReaderException('File not exists: ' . $this->filePath);
        }

        $content = false;

        // Open for reading in binary mode
        // (reading will not be interrupted at zero characters (ASCII 0)).
        $counter = 0;
        while ($counter < $this->openTries) {
            if ($this->resource = @fopen($this->filePath, 'rb')) {
                break;
            }
            $counter++;
            usleep(mt_rand(70000, 130000));
        }

        if (!is_resource($this->resource)) {
            throw new WriterException('Unable to open resource for file ' . $this->filePath);
        }

        if (!$this->sharedLock()) {
            throw new WriterException('Unable to get shared lock for file ' . $this->filePath);
        }

        $counter = 0;
        $fileSize = @filesize($this->filePath);
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

        if (!$content) {
            throw new ReaderException('Unable to read file ' . $this->filePath);
        }

        return $content;
    }

}
