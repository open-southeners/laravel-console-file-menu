<?php

namespace OpenSoutheners\LaravelConsoleFileMenu\Contracts;

use Closure;

interface FileMenuDriver
{
    public function customise(Closure $callback): void;

    /**
     * @return $this
     */
    public function disableDefaultItems(): self;

    /**
     * @param  array<string, string>  $options
     * @return $this
     */
    public function addOptions(array $options): self;

    public function open(): ?string;
}
