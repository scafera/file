<?php

declare(strict_types=1);

namespace Scafera\File;

use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\Mime\MimeTypes;

final class UploadedFile
{
    /** @internal Constructed by UploadExtractor — not for userland instantiation. */
    public function __construct(private readonly SymfonyUploadedFile $inner) {}

    public function getOriginalName(): string
    {
        return $this->inner->getClientOriginalName();
    }

    public function getExtension(): string
    {
        return $this->inner->getClientOriginalExtension();
    }

    public function getMimeType(): string
    {
        $mimeTypes = MimeTypes::getDefault();
        $guessed = $mimeTypes->guessMimeType($this->inner->getPathname());

        return $guessed ?? $this->inner->getClientMimeType();
    }

    public function getSize(): int
    {
        return $this->inner->getSize();
    }

    public function moveTo(string $directory, ?string $filename = null): string
    {
        $file = $this->inner->move($directory, $filename ?? $this->inner->getClientOriginalName());

        return $file->getPathname();
    }

    public function isValid(): bool
    {
        return $this->inner->isValid();
    }

    /** @internal */
    public function getPathname(): string
    {
        return $this->inner->getPathname();
    }
}
