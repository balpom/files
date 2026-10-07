<?php

declare(strict_types=1);

namespace Balpom\Files;

abstract class Handler implements PathInterface, TimeInterface
{
    protected string $absolutePath;
    protected string $rootDirectory;
    protected string $baseDirectory;
    private bool|null $windows = null;
    private string|false|null $windowsCodePage = null;

    public function __construct(string|null $path = null, string|null $baseDirectory = null, int $umask = 0022)
    {
        if (!$this->isWindows()) {
            $this->rootDirectory = '/';
        } else {
            $this->rootDirectory = $this->getRootDirectory(__DIR__);
        }
        $this->base($baseDirectory);
        if (null !== $path && '' !== $path) {
            $this->set($path, $umask);
        }
    }

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
    public function base(string|null $baseDirectory = null): PathInterface
    {
        $this->baseDirectory = $this->sanitizeBaseDirectory($baseDirectory);
        return $this;
    }

    /*
     * Set file or directory name (path from base directory or absolute path,
     * if base directory not set) and UMASK.
     */
    abstract public function set(string $path, int $umask = 0022): PathInterface;

    /*
     * Check file or directory existence.
     */
    public function exists(): bool
    {
        @clearstatcache(true, $this->absolutePath);
        return @file_exists($this->absolutePath);
    }

    /*
     * Get file or directory creation time (Unix timestamp).
     */
    public function getTime(): int|false
    {
        @clearstatcache(true, $this->absolutePath);
        return @filemtime($this->absolutePath);
    }

    /*
     * Set file or directory creation time (Unix timestamp).
     */
    public function setTime(int|null $time = null): bool
    {
        if (!$this->exists()) {
            return false;
        }
        return @touch($this->absolutePath, $time);
    }

    protected function init(string $path = ''): void
    {
        if ('' === $path) {
            throw new HandlerException('Empty path!');
        }

        $pathRootDirectory = $this->getRootDirectory($path);
        if (false === $pathRootDirectory) {
            // Given path is relative.
            $path = $this->preparePath($path);
            if ('' === $path) {
                throw new HandlerException('Empty relative path!');
            }
            if (!$this->isCorrectRelativePath($path)) {
                throw new HandlerException('Incorrect relative path!');
            }
            if ('' !== $this->baseDirectory) {
                $this->absolutePath = $this->baseDirectory . $path;
            } else {
                $this->absolutePath = $this->rootDirectory . $path;
            }
        } else {
            $path = substr($path, strlen($pathRootDirectory));
            $path = $this->preparePath($path);
            if (!$this->isCorrectRelativePath($path)) {
                throw new HandlerException('Incorrect absolute path!');
            }

            $this->absolutePath = $pathRootDirectory . $path;

            return;

            // Всё, что ниже - постепенно удалить!
//
// die($pathRootDirectory . ' --- ' . $path . ' === ' . $this->baseDirectory);


            $absolutePath = $pathRootDirectory . $path;

            if ('' !== $this->baseDirectory) {
                $baseDirectoryLen = strlen($this->baseDirectory);
                $absolutePathSubstr = substr($absolutePath, 0, $baseDirectoryLen);

// die($absolutePath . PHP_EOL . $this->baseDirectory . PHP_EOL . substr($absolutePath, 0, $baseDirectoryLen) . PHP_EOL);

                if ($this->baseDirectory !== $absolutePathSubstr) {
                    if ('/' !== substr($absolutePath, -1)) {
                        throw new HandlerException('Absolute file path must be in base directory, if it is not empty');
                    }
                    $absolutePathLen = strlen($absolutePath);
                    $baseDirectorySubstr = substr($this->baseDirectory, 0, $absolutePathLen);
                    if ($absolutePath !== $baseDirectorySubstr) {
                        throw new HandlerException('Absolute directory path must be part of base directory, if it is not empty');
                    }

                    // Lengthen the shorter absolute path.
                    // As sample:
                    // $baseDirectory = "/var/www/app/data/subdir/"
                    // $absolutePathSubstr = "/var/www/app/data/"

                    /*
                      $this->absolutePath = $this->baseDirectory;
                      return;
                     */
                }
            }

            $this->absolutePath = $pathRootDirectory . $path;
        }
    }

    protected function preparePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|/+|', '/', $path);
        $path = ltrim($path, '/');

        $windowsCodePage = $this->getWindowsCodePage();
        if (false !== $windowsCodePage) {
            $path = iconv('utf-8', $windowsCodePage, $path);
        }

        return $path;
    }

    protected function sanitizeBaseDirectory(string|null $baseDirectory): string
    {
        if (null === $baseDirectory || '' === $baseDirectory) {
            return '';
        }

        $rootDir = $this->getRootDirectory($baseDirectory);
        if (false === $rootDir) {
            throw new HandlerException('Base directory must be absolute!');
        } else {
            $relative = substr($baseDirectory, strlen($rootDir));
            $relative = $this->preparePath($relative);
            if (!$this->isCorrectRelativePath($relative)) {
                throw new HandlerException('Incorrect base directory!');
            }
            $baseDirectory = $rootDir . $relative;
        }

        return $baseDirectory;
    }

    protected function isCorrectRelativePath(string $path = ''): bool
    {
        if (preg_match('/([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])/u', $path)) {
            return false;
        }
        if ($this->isWindows() && strpbrk($path, ':*?<>|"')) {
            return false;
        }
        if (false !== strpos($path, '/.')) {
            return false;
        }
        if (substr_count($path, '.') === strlen($path)) {
            return false;
        }

        return true;
    }

    /*
     * If return FALSE - $path is relative.
     *
     * Based on https://habr.com/articles/731628/
     */
    protected function getRootDirectory(string $path): string|false
    {
        if (3 <= strlen($path) && ctype_alpha($path[0]) && $path[1] == ':' && ($path[2] == '/' || $path[2] == '\\')) {
            if (!$this->isWindows()) {
                // Under Linux "C:\" - correct file or directory name! And it's relative.
                return false;
            } else {
                // Windows disk path C:\
                return substr($path, 0, 3);
            }
        }

        if ('\\\\' === substr($path, 0, 2)) {
            if (!$this->isWindows()) {
                // Under Linux "\\" - correct file or directory name! And it's relative.
                return false;
            } else {
                if (7 <= strlen($path) && '.' === $path[3] || '?' === $path[3]) {
                    // Windows paths similar to \\?\D:\Plans\Marshall or \\.\D:\Projects\Human_Genome
                    if (!ctype_alpha($path[5]) || ':' !== $path[6]) {
                        throw new HandlerException('Incorrect path.');
                    }
                    $from = 7;
                } else {
                    // Windows UNC path similar to \\192.168.1.2\Pictures\Worth or \\host\path\subpath\subsubpath
                    // Now path as \\host/path/subpath/subsubpath - Windows don't open this path!
                    // It MUST be as \\host\path/subpath/subsubpath ("\" after hostname).
                    // Now $first = "\\" and $last = "host\path/subpath/subsubpath"
                    $pos = strpos($path, '/');
                    if (false === $pos) {
                        $pos = strpos($path, '\\');
                    }

                    if (false === $pos) {
                        // Windows UNC path similar to \\192.168.1.2 or \\host
                        // (without slash on the end).
                        return $path . '/';
                    }

                    $from = $pos + 1;
                }

                return substr($path, 0, $from);
            }
        }

        if ('/' === substr($path, 0, 1)) {
            if (!$this->isWindows()) {
                // Under Linux "/" - root directory.
                return '/';
            } else {
                return false;
            }
        }

        return false;
    }

    protected function isWindows(): bool
    {
        if (null === $this->windows) {
            // php_uname('s') under "seven" outputs "Windows NT".
            // PHP_OS contains "WINNT".
            $this->windows = false;
            if ((function_exists('php_uname') && 0 === @strncasecmp(@php_uname('s'), 'Windows', 7)) || (@defined('PHP_OS') && 0 === @strncasecmp(PHP_OS, 'Win', 3))) {
                $this->windows = true;
            }
        }

        return $this->windows;
    }

    protected function getWindowsCodePage(): string|false
    {
        if (null === $this->windowsCodePage) {
            if (!$this->isWindows()) {
                $this->windowsCodePage = false;
            } else {
                // Based on https://stackoverflow.com/questions/20408377/
                $this->windowsCodePage = ('C' == setlocale(LC_CTYPE, 0)) ? 'Windows-' . trim(strstr(setlocale(LC_CTYPE, ''), '.'), '.') : 'Windows-' . trim(strstr(setlocale(LC_CTYPE, 0), '.'), '.');
            }
        }

        return $this->windowsCodePage;
    }

}