<?php

declare(strict_types=1);

namespace Scafera\File\DependencyInjection;

use Scafera\Kernel\Tool\FileFinder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal Enforces that Symfony file/filesystem types do not leak into userland code.
 */
final class FileBoundaryPass implements CompilerPassInterface
{
    /** @var array<string, string> Regex pattern => violation message */
    public const FORBIDDEN_PATTERNS = [
        'Symfony\\\\Component\\\\HttpFoundation\\\\File\\\\UploadedFile' => 'Symfony UploadedFile — use Scafera\\File\\UploadedFile instead',
        'Symfony\\\\Component\\\\HttpFoundation\\\\File\\\\File' => 'Symfony File — use Scafera\\File types instead',
        'Symfony\\\\Component\\\\Filesystem\\\\' => 'Symfony Filesystem — use Scafera\\File\\FileStorage instead',
    ];

    public function process(ContainerBuilder $container): void
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        $srcDir = $projectDir . '/src';

        if (!is_dir($srcDir)) {
            return;
        }

        $violations = [];

        foreach (FileFinder::findPhpFiles($srcDir) as $file) {
            $relative = str_replace($projectDir . '/', '', $file);
            $contents = file_get_contents($file);

            foreach (self::FORBIDDEN_PATTERNS as $pattern => $message) {
                if (self::matches($contents, $pattern)) {
                    $violations[] = "  - {$relative}: uses {$message}";
                }
            }
        }

        if (!empty($violations)) {
            throw new \LogicException(
                "Scafera\\File boundary violation:\n\n"
                . implode("\n", $violations)
                . "\n\nUse Scafera\\File types instead (UploadedFile, UploadExtractor, FileStorage, FileResponse).",
            );
        }
    }

    public static function matches(string $contents, string $pattern): bool
    {
        return (bool) preg_match('/^use\s+' . $pattern . '/m', $contents)
            || (bool) preg_match('/new\s+\\\\?' . $pattern . '/m', $contents)
            || (bool) preg_match('/extends\s+\\\\?' . $pattern . '/m', $contents);
    }
}
