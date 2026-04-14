<?php

declare(strict_types=1);

namespace Scafera\File;

final readonly class UploadConstraint
{
    /**
     * @param list<string> $allowedExtensions
     * @param list<string> $allowedMimeTypes
     */
    public function __construct(
        public array $allowedExtensions = [],
        public array $allowedMimeTypes = [],
        public int $maxSizeBytes = 10_485_760,
    ) {}
}
