<?php

declare(strict_types=1);

namespace Scafera\File;

use Symfony\Component\Filesystem\Filesystem;

final class FileStorage
{
    private readonly Filesystem $filesystem;

    public function __construct(private readonly string $storagePath)
    {
        $this->filesystem = new Filesystem();
    }

    public function store(UploadedFile $file, string $directory, ?string $filename = null): string
    {
        // 1. Sanitize filename and directory — strip traversal components
        $targetName = $this->sanitizeFilename($filename ?? $file->getOriginalName());
        $safeDir = $this->sanitizeDirectory($directory);

        // 2. Validate the intended path is inside storage — BEFORE any filesystem operations
        $targetDir = $this->storagePath . '/' . $safeDir;
        $storagePath = $this->resolveStoragePath();

        // Ensure storage root exists
        if (!$this->filesystem->exists($this->storagePath)) {
            $this->filesystem->mkdir($this->storagePath);
        }

        // 3. Create the target directory (safe — traversal components already stripped by sanitizeDirectory)
        if (!$this->filesystem->exists($targetDir)) {
            $this->filesystem->mkdir($targetDir);
        }

        // 4. Final validation after mkdir — confirm resolved path is inside storage
        $resolvedDir = realpath($targetDir);
        if ($resolvedDir === false || !str_starts_with($resolvedDir, $storagePath)) {
            throw new \RuntimeException('Path traversal detected: target directory is outside the storage directory.');
        }

        // 5. Handle collision — append unique suffix if file already exists
        $targetName = $this->uniqueFilename($resolvedDir, $targetName);

        // 6. Move file to verified-safe location
        $file->moveTo($resolvedDir, $targetName);

        return $safeDir . '/' . $targetName;
    }

    public function exists(string $path): bool
    {
        $resolved = $this->resolveAndValidate($path);

        return $resolved !== null && is_file($resolved);
    }

    public function delete(string $path): bool
    {
        $resolved = $this->resolveAndValidate($path);

        if ($resolved === null || !is_file($resolved)) {
            return false;
        }

        $this->filesystem->remove($resolved);

        return true;
    }

    private function sanitizeDirectory(string $directory): string
    {
        // Strip leading/trailing slashes, remove .. and . segments
        $parts = explode('/', trim($directory, '/'));
        $safe = array_filter($parts, fn(string $part) => $part !== '' && $part !== '.' && $part !== '..');

        if ($safe === []) {
            throw new \RuntimeException('Invalid directory path: resolved to empty after sanitization.');
        }

        return implode('/', $safe);
    }

    private function sanitizeFilename(string $filename): string
    {
        // Strip directory components
        $filename = basename($filename);

        // Remove null bytes and other dangerous characters
        $filename = str_replace(["\0", '/', '\\'], '', $filename);

        if ($filename === '' || $filename === '.' || $filename === '..') {
            $filename = 'unnamed_' . bin2hex(random_bytes(4));
        }

        return $filename;
    }

    private function uniqueFilename(string $directory, string $filename): string
    {
        if (!file_exists($directory . '/' . $filename)) {
            return $filename;
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $suffix = bin2hex(random_bytes(4));

        return $ext !== '' ? "{$base}_{$suffix}.{$ext}" : "{$base}_{$suffix}";
    }

    private function resolveAndValidate(string $path): ?string
    {
        $fullPath = $this->storagePath . '/' . $path;
        $resolved = realpath($fullPath);

        if ($resolved === false || !str_starts_with($resolved, $this->resolveStoragePath())) {
            return null;
        }

        return $resolved;
    }

    private function resolveStoragePath(): string
    {
        return realpath($this->storagePath) ?: $this->storagePath;
    }
}
