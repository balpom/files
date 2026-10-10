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
     * Check a file or a directory path for limitations.
     */
    abstract protected function checkPath(string $path): void;

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

        // 15032385535 is the maximum value for a timestamp that ext4 can store.
        // See also https://www.linuxquestions.org/questions/linux-kernel-70/ext4-timestamps-a-puzzler-4175572339/
        if (15032385535 < $time && 'ext4' === $this->getFileSystemForLinux($this->absolutePath)) {
            throw new HandlerException('Time value is out of range for EXT4 file system (max 15032385535, given ' . $time . ')!');
        }

        // -2147483648 is the minimum value for a timestamp that ext4 can store.
        if (-2147483648 > $time && 'ext4' === $this->getFileSystemForLinux($this->absolutePath)) {
            throw new HandlerException('Time value is out of range for EXT4 file system (min -2147483648, given ' . $time . ')!');
        }

        // 253402289999 is 9999-12-31 23:59:59
        // On my Windows system max time value, with which touch($time) works correctly, is 910692730085.
        // 910692730085 is 30828-09-14 02:48:05 - what is means i dont't know. :-)
        if (910692730085 < $time && $this->isWindows()) {
            throw new HandlerException('Time value is out of range for Windows (max 253402289999, given ' . $time . ')!');
        }

        // On my Windows system code
        // touch(-1);
        // filemtime($path);
        // returns 1844674407369 (2^64 = 18446744073709551616 like 1844674407370 = 1844674407369 + 1).
        if (0 > $time && $this->isWindows()) {
            throw new HandlerException('Time value must be positive for Windows (given ' . $time . ')!');
        }

        return @touch($this->absolutePath, $time);
    }

    protected function init(string $path = ''): void
    {
        if ('' === $path) {
            throw new HandlerException('Empty path!');
        }

        $path = $this->preparePath($path);
        $pathRootDirectory = $this->getRootDirectory($path);
        if (false === $pathRootDirectory) {
            // Given path is relative.
            if ('' === $path) {
                throw new HandlerException('Empty relative path!');
            }
            try {
                $this->checkPathCommon($path);
            } catch (PathException $e) {
                throw new HandlerException('Incorrect relative path!');
            }
            if ('' !== $this->baseDirectory) {
                $this->absolutePath = $this->baseDirectory . $path;
            } else {
                $this->absolutePath = $this->rootDirectory . $path;
            }
        } else {
            $path = substr($path, strlen($pathRootDirectory));
            try {
                $this->checkPathCommon($path);
            } catch (PathException $e) {
                throw new HandlerException('Incorrect absolute path!');
            }
            $this->absolutePath = $pathRootDirectory . $path;
        }
    }

    protected function preparePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|/+|', '/', $path);

        $windowsCodePage = $this->getWindowsCodePage();
        if (false !== $windowsCodePage) {
            $path = iconv('utf-8', $windowsCodePage, $path);
        }

        return $path;
    }

    protected function checkPathCommon(string $path = ''): void
    {
        if (preg_match('/([^\pL\pN\pP\pS\pZ])|([\xC2\xA0])/u', $path)) {
            throw new PathException('Invalid characters in the path!');
        }

        $baseName = pathinfo($path, PATHINFO_BASENAME,);
        $pathLenght = strlen($path);
        if ($this->isWindows()) {
            if (260 < $pathLenght) {
                throw new PathException('For Windows path lenght is out of range!');
            }
            if (strpbrk($path, ':*?<>|"')) {
                throw new PathException('For Windows it is invalid characters in the path!');
            }
            // "/path/./sample1" or "/path/../sample2" or "/path/...../sample3" or "/path/sample4/....."
            // is illegal for Windows
            if (preg_match('(/\.+/)', $path) || preg_match('(/\.+/)', $baseName)) {
                throw new PathException('For Windows path parts cannot consist only from points!');
            }
        } else {
            // TODO: File systems other than EXT4 may have their own limitations.
            if (4096 < $pathLenght) {
                throw new PathException('For Linux path lenght is out of range!');
            }
            // "/path/./sample1" or "/path/../sample2" or "/path/sample3/." or "/path/sample4/.."
            // is illegal for Linux.
            // The file or directory name can consist of only three or more dots.
            // In other words, "/path/.../sample" or "/path/sample/..." is legal for Linux!
            if (preg_match('(/(\.){1,2}/)', $path) || preg_match('(/\.+/)', $path)) {
                throw new PathException('For Windows path parts cannot consist only from points!');
            }
        }

        $this->checkPath($path);
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
            try {
                $this->checkPathCommon($relative);
            } catch (PathException $e) {
                throw new HandlerException('Incorrect base directory!');
            }
            $baseDirectory = $rootDir . $relative;
        }

        return $baseDirectory;
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

    protected function getFileSystemForLinux(string $absolutePath): string|false
    {
        if ($this->isWindows()) {
            return false;
        }

        try {
            $output = shell_exec('df -Th | grep "^/dev"');
            if (empty($output)) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        $lines = explode(chr(10), $output); // /dev/md1 ext4 687G 32G 621G 5% /var
        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }
            $line = trim(preg_replace('/\s+/', ' ', $line));
            $field = explode(' ', $line);
            $len = strlen($field[6]);
            if ($field[6] <> substr($absolutePath, 0, 4)) {
                continue;
            }

            return $field[1];
        }

        return false;
    }

}