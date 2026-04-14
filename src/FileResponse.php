<?php

declare(strict_types=1);

namespace Scafera\File;

final readonly class FileResponse
{
    private function __construct(
        private string $path,
        private ?string $filename,
        private string $disposition,
    ) {}

    public static function download(string $path, ?string $filename = null): self
    {
        return new self($path, $filename, 'attachment');
    }

    public static function inline(string $path): self
    {
        return new self($path, null, 'inline');
    }

    /** @internal */
    public function getPath(): string
    {
        return $this->path;
    }

    /** @internal */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /** @internal */
    public function getDisposition(): string
    {
        return $this->disposition;
    }
}
