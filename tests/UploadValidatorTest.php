<?php

declare(strict_types=1);

namespace Scafera\File\Tests;

use PHPUnit\Framework\TestCase;
use Scafera\File\UploadConstraint;
use Scafera\File\UploadedFile;
use Scafera\File\UploadResult;
use Scafera\File\UploadValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

final class UploadValidatorTest extends TestCase
{
    private function createUploadedFile(string $name = 'test.jpg', int $size = 1024, bool $valid = true): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'scafera_test_');
        file_put_contents($tmpFile, str_repeat('x', $size));

        $inner = new SymfonyUploadedFile(
            $tmpFile,
            $name,
            'image/jpeg',
            $valid ? \UPLOAD_ERR_OK : \UPLOAD_ERR_INI_SIZE,
            true,
        );

        return new UploadedFile($inner);
    }

    public function testValidFilePassesValidation(): void
    {
        $file = $this->createUploadedFile();
        $constraint = new UploadConstraint();
        $validator = new UploadValidator();

        $result = $validator->validate($file, $constraint);

        $this->assertTrue($result->isValid());
        $this->assertNull($result->error());
    }

    public function testFileTooLargeFailsValidation(): void
    {
        $file = $this->createUploadedFile(size: 2048);
        $constraint = new UploadConstraint(maxSizeBytes: 1024);
        $validator = new UploadValidator();

        $result = $validator->validate($file, $constraint);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('exceeds maximum size', $result->error());
    }

    public function testDisallowedExtensionFailsValidation(): void
    {
        $file = $this->createUploadedFile(name: 'test.exe');
        $constraint = new UploadConstraint(allowedExtensions: ['jpg', 'png']);
        $validator = new UploadValidator();

        $result = $validator->validate($file, $constraint);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('extension', $result->error());
    }
}
