<?php

declare(strict_types=1);

namespace Scafera\File\Tests;

use PHPUnit\Framework\TestCase;
use Scafera\File\FileStorage;
use Scafera\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

final class FileStorageTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/scafera_file_storage_' . uniqid();
        mkdir($this->tmpDir . '/storage', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    public function testPathTraversalStripsDoubleDotSegments(): void
    {
        $storage = new FileStorage($this->tmpDir . '/storage');
        $file = $this->createUploadedFile('innocent.txt');

        // ../../etc is sanitized to 'etc' — file lands safely inside storage
        $path = $storage->store($file, '../../etc');

        $this->assertSame('etc/innocent.txt', $path);
        $this->assertTrue($storage->exists($path));

        // Verify no file exists outside storage
        $this->assertFileDoesNotExist($this->tmpDir . '/etc/innocent.txt');
    }

    public function testCollisionAppendsUniqueSuffix(): void
    {
        $storage = new FileStorage($this->tmpDir . '/storage');

        // Store first file
        $file1 = $this->createUploadedFile('report.txt');
        $path1 = $storage->store($file1, 'docs');

        // Store second file with same name
        $file2 = $this->createUploadedFile('report.txt');
        $path2 = $storage->store($file2, 'docs');

        $this->assertNotSame($path1, $path2);
        $this->assertTrue($storage->exists($path1));
        $this->assertTrue($storage->exists($path2));
    }

    public function testSanitizesFilename(): void
    {
        $storage = new FileStorage($this->tmpDir . '/storage');
        $file = $this->createUploadedFile('../../../etc/passwd');

        $path = $storage->store($file, 'uploads');

        // Should strip directory components, keeping only the basename
        $this->assertStringNotContainsString('..', $path);
        $this->assertTrue($storage->exists($path));
    }

    private function createUploadedFile(string $name): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'scafera_test_');
        file_put_contents($tmpFile, 'test content');

        $inner = new SymfonyUploadedFile($tmpFile, $name, 'text/plain', \UPLOAD_ERR_OK, true);

        return new UploadedFile($inner);
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
