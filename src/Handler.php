<?php

declare(strict_types=1);

namespace Balpom\Files;

abstract class Handler implements PathInterface, TimeInterface
{
    protected string $absolutePath;
    protected string $rootDirectory = '/';
    private bool|null $windows = null;
    private string|false|null $windowsCodePage = null;

    public function __construct(string $absolutePath = '', int $umask = 0022)
    {
        if (!empty($absolutePath)) {
            $this->set($absolutePath, $umask);
        }
    }

    /*
     * Set file or directory name (absolute path) and UMASK.
     */
    abstract public function set(string $absolutePath, int $umask = 0022): PathInterface;

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

    protected function init(string $absolutePath = ''): void
    {
        if (empty($absolutePath)) {
            throw new HandlerException('Empty file name!');
        }
        $first = $this->unchangeablePathPart($absolutePath);
        if (!empty($first)) {
            if (!$this->isWindows()) {
                throw new HandlerException('Windows style path cannot be used under Unix-like systems.');
            }
            $from = strlen($first);
        } else {
            if ($this->isWindows()) {
                throw new HandlerException('Only absolute path may be used.');
            } else {
                $first = substr($absolutePath, 0, 1);
                if ('\\' === $first) { // Just in case. Unix-style absolute path beginning from "/".
                    $first = '/';
                }
                if ('/' !== $first) {
                    throw new HandlerException('Only absolute path may be used.');
                }
                $from = 1;
            }
        }

        $last = substr($absolutePath, $from);
        $last = str_replace('\\', '/', $last);
        $last = preg_replace('|/+|', '/', $last);
        $last = ltrim($last, '/');

        if (2 === $from) { // Windows UNC path similar to \\192.168.1.2\Pictures\Worth or \\host\path\subpath\subsubpath
            // Now path as \\host/path/subpath/subsubpath - Windows don't open this path!
            // It MUST be as \\host\path/subpath/subsubpath ("\" after hostname).
            // Now $first = "\\" and $last = "host\path/subpath/subsubpath"
            $pos = strpos($last, '/');
            if (false !== $pos) {
                $first = $first . '\\' . substr($last, 0, $pos); // Now $first = "\\host\"
                $last = substr($last, $pos + 1); // Now $last = "path/subpath/subsubpath"
            }
        }

        if (!$this->isCorrectPath($last)) {
            throw new HandlerException('Incorrect path name!');
        }

        if ($windowsCodePage = $this->getWindowsCodePage()) {
            $last = iconv('utf-8', $windowsCodePage, $last);
        }

        $this->absolutePath = $first . $last;
        $this->rootDirectory = $first;
    }

    protected function isCorrectPath(string $path = ''): bool
    {
        if ('' === $path) {
            return false;
        }
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

    protected function unchangeablePathPart(string $path): string
    {
        // Based on https://habr.com/articles/731628/
        if (ctype_alpha($path[0]) && $path[1] == ':' && ($path[2] == '/' || $path[2] == '\\')) {
            return substr($path, 0, 3); // Windows disk path C:\
        }
        if ('\\\\' !== substr($path, 0, 2)) {
            return '';
        }
        if ('.' === $path[3] || '?' === $path[3]) { // Windows paths similar to \\?\D:\Plans\Marshall or \\.\D:\Projects\Human_Genome
            if (!ctype_alpha($path[5]) || ':' !== $path[6]) {
                throw new HandlerException('Incorrect path.');
            }
            $from = 7;
        } else { // Windows UNC path similar to \\192.168.1.2\Pictures\Worth or \\host\path\subpath\subsubpath
            $from = 2;
        }

        return substr($path, 0, $from);
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