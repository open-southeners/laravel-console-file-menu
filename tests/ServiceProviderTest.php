<?php

namespace OpenSoutheners\LaravelConsoleFileMenu\Tests;

use Illuminate\Console\Command;
use Illuminate\Container\Container;
use OpenSoutheners\LaravelConsoleFileMenu\FileMenu;
use OpenSoutheners\LaravelConsoleFileMenu\ServiceProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ServiceProviderTest extends TestCase
{
    public function test_it_registers_file_menu_command_macro(): void
    {
        $provider = new ServiceProvider(new Container);

        $provider->boot();

        $this->assertTrue(Command::hasMacro('fileMenu'));
    }

    public function test_file_menu_macro_returns_file_menu_for_base_path(): void
    {
        $basePath = sys_get_temp_dir();
        $provider = new ServiceProvider(new Container);
        $command = new class extends Command
        {
            protected $signature = 'test:command';
        };

        $provider->boot();

        $fileMenu = $command->fileMenu($basePath);

        $this->assertInstanceOf(FileMenu::class, $fileMenu);

        $basePathProperty = new ReflectionProperty($fileMenu, 'basePath');

        $this->assertSame($basePath, $basePathProperty->getValue($fileMenu));
    }
}
