<?php

namespace OpenSoutheners\LaravelConsoleFileMenu;

use Closure;
use Illuminate\Support\Str;
use NunoMaduro\LaravelConsoleMenu\Menu;
use OpenSoutheners\LaravelConsoleFileMenu\Contracts\FileMenuDriver;
use Symfony\Component\Finder\Finder;

class FileMenu
{
    private string $currentPath;

    private ?Closure $customiseCallback = null;

    private bool $directoriesOnly = false;

    private bool $fileExtensions = true;

    private bool $ignored = true;

    /**
     * @param  (Closure(string): FileMenuDriver)|null  $menuFactory
     */
    public function __construct(private string $basePath = '', private ?Closure $menuFactory = null)
    {
        $this->basePath = $basePath ?: (getcwd() ?: '');
        $this->currentPath = $this->basePath;
    }

    /**
     * Customize the menu appearance.
     *
     * @link https://github.com/php-school/cli-menu?tab=readme-ov-file#appearance
     */
    public function customise(Closure $callback): self
    {
        $this->customiseCallback = $callback;

        return $this;
    }

    /**
     * Show only directories.
     */
    public function onlyDirectories(bool $value = true): self
    {
        $this->directoriesOnly = $value;

        return $this;
    }

    /**
     * Hide files extensions from menu labels (output will still get them).
     */
    public function hideFileExtensions(bool $value = true): self
    {
        $this->fileExtensions = ! $value;

        return $this;
    }

    /**
     * Respect ignored paths from gitignored-like files.
     *
     * @link https://symfony.com/doc/current/components/finder.html#version-control-files
     */
    public function respectIgnored(bool $value = true): self
    {
        $this->ignored = $value;

        return $this;
    }

    /**
     * Open the menu and return if selected path.
     */
    public function open(): ?string
    {
        $pickedPath = null;

        while (! $pickedPath) {
            $menu = $this->makeMenu($this->currentPath);

            if ($this->customiseCallback) {
                $menu->customise($this->customiseCallback);
            }

            $menuSelection = $menu->disableDefaultItems()
                ->addOptions($this->scratchSurface())
                ->open();

            if (is_string($menuSelection) && is_file($menuSelection)) {
                $this->currentPath = $menuSelection;
            }

            if (! $menuSelection || $menuSelection === $this->currentPath) {
                $pickedPath = $this->currentPath;
            }

            $this->currentPath = $menuSelection ?? $this->currentPath;
        }

        return $pickedPath;
    }

    private function makeMenu(string $path): FileMenuDriver
    {
        if ($this->menuFactory) {
            return call_user_func($this->menuFactory, $path);
        }

        return new LaravelConsoleMenuDriver(new Menu($path));
    }

    /**
     * Scratch first level of files (surface) from current path.
     *
     * @return array<string, string>
     */
    private function scratchSurface(): array
    {
        $fileList = Finder::create()
            ->in($this->currentPath)
            ->depth(0)
            ->ignoreVCSIgnored($this->ignored);

        if ($this->directoriesOnly) {
            $fileList->directories();
        }

        $menuOptions[$this->currentPath] = '.';

        if ($this->basePath !== $this->currentPath) {
            $menuOptions[Str::beforeLast($this->currentPath, '/')] = '..';
        }

        foreach ($fileList as $file) {
            $menuOptions[$file->getPathname()] = $this->fileExtensions
                ? $file->getFilename()
                : $file->getFilenameWithoutExtension();
        }

        return $menuOptions;
    }
}
