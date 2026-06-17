<?php

namespace OpenSoutheners\LaravelConsoleFileMenu\Tests\Fakes;

use Closure;
use OpenSoutheners\LaravelConsoleFileMenu\Contracts\FileMenuDriver;

final class FakeMenuDriver implements FileMenuDriver
{
    /**
     * @var array<string, string>
     */
    public array $options = [];

    public bool $customised = false;

    public function __construct(public string $path, private ?string $selection) {}

    public function customise(Closure $callback): void
    {
        $this->customised = true;
    }

    public function disableDefaultItems(): FileMenuDriver
    {
        return $this;
    }

    public function addOptions(array $options): FileMenuDriver
    {
        $this->options = $options;

        return $this;
    }

    public function open(): ?string
    {
        return $this->selection;
    }
}
