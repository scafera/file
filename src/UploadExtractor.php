<?php

declare(strict_types=1);

namespace Scafera\File;

use Scafera\Kernel\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

final class UploadExtractor
{
    public function get(Request $request, string $field): ?UploadedFile
    {
        $file = $request->getSymfonyRequest()->files->get($field);

        if (!$file instanceof SymfonyUploadedFile) {
            return null;
        }

        return new UploadedFile($file);
    }

    public function has(Request $request, string $field): bool
    {
        return $request->getSymfonyRequest()->files->has($field);
    }
}
