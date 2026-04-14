<?php

declare(strict_types=1);

namespace Scafera\File\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Scafera\File\DependencyInjection\FileBoundaryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class FileBoundaryPassTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/scafera_file_boundary_' . uniqid();
        mkdir($this->tmpDir . '/src', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testBlocksSymfonyUploadedFileImport(): void
    {
        file_put_contents($this->tmpDir . '/src/Bad.php', <<<'PHP'
        <?php
        use Symfony\Component\HttpFoundation\File\UploadedFile;
        class Bad {}
        PHP);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Symfony UploadedFile/');

        $this->runPass();
    }

    public function testBlocksSymfonyFileImport(): void
    {
        file_put_contents($this->tmpDir . '/src/Bad.php', <<<'PHP'
        <?php
        use Symfony\Component\HttpFoundation\File\File;
        class Bad {}
        PHP);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Symfony File/');

        $this->runPass();
    }

    public function testBlocksSymfonyFilesystemImport(): void
    {
        file_put_contents($this->tmpDir . '/src/Bad.php', <<<'PHP'
        <?php
        use Symfony\Component\Filesystem\Filesystem;
        class Bad {}
        PHP);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Symfony Filesystem/');

        $this->runPass();
    }

    public function testBlocksNewFqcnInstantiation(): void
    {
        file_put_contents($this->tmpDir . '/src/Bad.php', <<<'PHP'
        <?php
        class Bad {
            public function run() {
                $fs = new \Symfony\Component\Filesystem\Filesystem();
            }
        }
        PHP);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/Symfony Filesystem/');

        $this->runPass();
    }

    public function testAllowsScaferaFileImports(): void
    {
        file_put_contents($this->tmpDir . '/src/Good.php', <<<'PHP'
        <?php
        use Scafera\File\UploadedFile;
        use Scafera\File\FileStorage;
        class Good {}
        PHP);

        $this->runPass();
        $this->assertTrue(true);
    }

    private function runPass(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->tmpDir);

        $pass = new FileBoundaryPass();
        $pass->process($container);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        ) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($dir);
    }
}
