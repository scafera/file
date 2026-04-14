<?php

declare(strict_types=1);

namespace Scafera\File\Validator;

use Scafera\File\DependencyInjection\FileBoundaryPass;
use Scafera\Kernel\Contract\ValidatorInterface;
use Scafera\Kernel\Tool\FileFinder;

final class FileBoundaryValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'File Boundary';
    }

    public function validate(string $projectDir): array
    {
        $srcDir = $projectDir . '/src';

        if (!is_dir($srcDir)) {
            return [];
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            foreach (FileBoundaryPass::FORBIDDEN_PATTERNS as $pattern => $message) {
                if (FileBoundaryPass::matches($contents, $pattern)) {
                    $violations[] = "{$relative}: uses {$message}";
                }
            }
        }

        return $violations;
    }
}
