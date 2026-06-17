<?php

namespace OpenSoutheners\LaravelConsoleFileMenu;

use Closure;
use NunoMaduro\LaravelConsoleMenu\Menu;
use OpenSoutheners\LaravelConsoleFileMenu\Contracts\FileMenuDriver;

class LaravelConsoleMenuDriver implements FileMenuDriver
{
    public function __construct(private Menu $menu) {}

    public function customise(Closure $callback): void
    {
        call_user_func($callback, $this->menu);
    }

    public function disableDefaultItems(): FileMenuDriver
    {
        $this->menu->disableDefaultItems();

        return $this;
    }

    public function addOptions(array $options): FileMenuDriver
    {
        $this->menu->addOptions($options);

        return $this;
    }

    public function open(): ?string
    {
        $selection = $this->menu->open();

        return is_string($selection) ? $selection : null;
    }
}
