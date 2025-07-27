<?php

declare(strict_types=1);

namespace Balpom\Files;

abstract class Handler
{
    protected string $filePath;
    protected mixed $resource;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    protected function exclusiveLock(): bool
    {
        return $this->lock($this->resource, LOCK_EX | LOCK_NB);
    }

    protected function sharedLock(): bool
    {
        return $this->lock($this->resource, LOCK_SH | LOCK_NB);
    }

    protected function unlock(): void
    {
        @flock($this->resource, LOCK_UN);
    }

    private function lock(mixed $fh, $lockflag): bool
    {
        if (!is_resource($fh)) {
            throw new HandlerException('Bad argument for lock method.');
        }

        $tries = $this->lockTries;
        $locked = false;

        while ($tries > 0 or !$locked) {
            $locked = @flock($fh, $lockflag);
            if ($locked) {
                return true; // If the lock is setted, immediately returning TRUE.
            }
            if ($tries > 1) { // If only one attempt is given, then there is no need to wait if it was unsuccessful.
                usleep(mt_rand(70000, 130000));
            }
            $tries--;
        }

        return false;
    }

    protected function close(): void
    {
        @fflush($this->resource);
        $this->unlock();
        @fclose($this->resource);
        @clearstatcache();
    }

    public function __destruct()
    {
        if (is_resource($this->resource)) {
            $this->close(); // Just in case...
        }
    }

}
