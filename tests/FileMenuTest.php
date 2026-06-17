<?php

namespace OpenSoutheners\LaravelConsoleFileMenu\Tests;

use OpenSoutheners\LaravelConsoleFileMenu\FileMenu;
use OpenSoutheners\LaravelConsoleFileMenu\Tests\Fakes\FakeMenuDriver;
use PHPUnit\Framework\TestCase;

final class FileMenuTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->basePath = __DIR__.'/.runtime/laravel-console-file-menu-'.bin2hex(random_bytes(6));

        if (! is_dir(dirname($this->basePath))) {
            mkdir(dirname($this->basePath), recursive: true);
        }

        mkdir($this->basePath);
        mkdir($this->basePath.'/nested');
        file_put_contents($this->basePath.'/alpha.txt', 'alpha');
        file_put_contents($this->basePath.'/beta.php', '<?php echo "beta";');
        file_put_contents($this->basePath.'/nested/child.txt', 'child');
        file_put_contents($this->basePath.'/.gitignore', "ignored.log\n");
        file_put_contents($this->basePath.'/ignored.log', 'ignored');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        $this->removeDirectoryIfEmpty(dirname($this->basePath));

        parent::tearDown();
    }

    public function test_root_menu_includes_current_directory_without_parent_directory(): void
    {
        [$result, $menus] = $this->openWithSelections([$this->basePath]);

        $this->assertSame($this->basePath, $result);
        $this->assertSame('.', $menus[0]->options[$this->basePath]);
        $this->assertArrayNotHasKey(dirname($this->basePath), $menus[0]->options);
    }

    public function test_nested_menu_includes_parent_directory(): void
    {
        $nestedPath = $this->basePath.'/nested';

        [, $menus] = $this->openWithSelections([$nestedPath, $nestedPath]);

        $this->assertSame('..', $menus[1]->options[$this->basePath]);
    }

    public function test_menu_shows_only_depth_zero_entries(): void
    {
        [, $menus] = $this->openWithSelections([$this->basePath]);

        $this->assertArrayHasKey($this->basePath.'/nested', $menus[0]->options);
        $this->assertArrayHasKey($this->basePath.'/alpha.txt', $menus[0]->options);
        $this->assertArrayNotHasKey($this->basePath.'/nested/child.txt', $menus[0]->options);
    }

    public function test_only_directories_removes_files_from_options(): void
    {
        [, $menus] = $this->openWithSelections(
            [$this->basePath],
            fn (FileMenu $fileMenu) => $fileMenu->onlyDirectories(),
        );

        $this->assertArrayHasKey($this->basePath.'/nested', $menus[0]->options);
        $this->assertArrayNotHasKey($this->basePath.'/alpha.txt', $menus[0]->options);
        $this->assertArrayNotHasKey($this->basePath.'/beta.php', $menus[0]->options);
    }

    public function test_hide_file_extensions_changes_labels_but_keeps_paths(): void
    {
        [, $menus] = $this->openWithSelections(
            [$this->basePath],
            fn (FileMenu $fileMenu) => $fileMenu->hideFileExtensions(),
        );

        $this->assertSame('alpha', $menus[0]->options[$this->basePath.'/alpha.txt']);
        $this->assertSame('beta', $menus[0]->options[$this->basePath.'/beta.php']);
    }

    public function test_ignored_paths_are_excluded_by_default(): void
    {
        [, $menus] = $this->openWithSelections([$this->basePath]);

        $this->assertArrayNotHasKey($this->basePath.'/ignored.log', $menus[0]->options);
    }

    public function test_ignored_paths_can_be_included(): void
    {
        [, $menus] = $this->openWithSelections(
            [$this->basePath],
            fn (FileMenu $fileMenu) => $fileMenu->respectIgnored(false),
        );

        $this->assertSame('ignored.log', $menus[0]->options[$this->basePath.'/ignored.log']);
    }

    public function test_selecting_current_directory_returns_it(): void
    {
        [$result] = $this->openWithSelections([$this->basePath]);

        $this->assertSame($this->basePath, $result);
    }

    public function test_selecting_file_returns_file_path(): void
    {
        $filePath = $this->basePath.'/alpha.txt';

        [$result] = $this->openWithSelections([$filePath]);

        $this->assertSame($filePath, $result);
    }

    public function test_selecting_directory_opens_next_menu(): void
    {
        $nestedPath = $this->basePath.'/nested';

        [$result, $menus] = $this->openWithSelections([$nestedPath, $nestedPath]);

        $this->assertSame($nestedPath, $result);
        $this->assertSame([$this->basePath, $nestedPath], array_map(
            fn (FakeMenuDriver $menu) => $menu->path,
            $menus,
        ));
    }

    public function test_selecting_parent_directory_navigates_upward(): void
    {
        $nestedPath = $this->basePath.'/nested';

        [$result, $menus] = $this->openWithSelections([$nestedPath, $this->basePath, $this->basePath]);

        $this->assertSame($this->basePath, $result);
        $this->assertSame([$this->basePath, $nestedPath, $this->basePath], array_map(
            fn (FakeMenuDriver $menu) => $menu->path,
            $menus,
        ));
    }

    public function test_cancelling_menu_returns_current_directory(): void
    {
        [$result] = $this->openWithSelections([null]);

        $this->assertSame($this->basePath, $result);
    }

    public function test_customise_callback_is_passed_to_menu_driver(): void
    {
        [, $menus] = $this->openWithSelections(
            [$this->basePath],
            fn (FileMenu $fileMenu) => $fileMenu->customise(fn () => null),
        );

        $this->assertTrue($menus[0]->customised);
    }

    /**
     * @param  array<int, string|null>  $selections
     * @return array{0: string|null, 1: array<int, FakeMenuDriver>}
     */
    private function openWithSelections(array $selections, ?callable $configure = null): array
    {
        $menus = [];
        $selectionIndex = 0;

        $fileMenu = new FileMenu($this->basePath, function (string $path) use (&$menus, &$selectionIndex, $selections): FakeMenuDriver {
            $menu = new FakeMenuDriver($path, $selections[$selectionIndex] ?? null);
            $menus[] = $menu;
            $selectionIndex++;

            return $menu;
        });

        if ($configure) {
            $configure($fileMenu);
        }

        return [$fileMenu->open(), $menus];
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $itemPath = $path.'/'.$item;

            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);

                continue;
            }

            unlink($itemPath);
        }

        rmdir($path);
    }

    private function removeDirectoryIfEmpty(string $path): void
    {
        if (is_dir($path) && (scandir($path) ?: []) === ['.', '..']) {
            rmdir($path);
        }
    }
}
