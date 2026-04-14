<?php

declare(strict_types=1);

namespace Scafera\File;

final class UploadValidator
{
    public function validate(UploadedFile $file, UploadConstraint $constraint): UploadResult
    {
        if (!$file->isValid()) {
            return UploadResult::invalid('File upload failed.');
        }

        if ($file->getSize() > $constraint->maxSizeBytes) {
            $maxMb = round($constraint->maxSizeBytes / 1_048_576, 1);

            return UploadResult::invalid(sprintf('File exceeds maximum size of %s MB.', $maxMb));
        }

        if ($constraint->allowedExtensions !== []) {
            $ext = strtolower($file->getExtension());
            $allowed = array_map('strtolower', $constraint->allowedExtensions);

            if (!\in_array($ext, $allowed, true)) {
                return UploadResult::invalid(sprintf(
                    'File extension "%s" is not allowed. Allowed: %s.',
                    $ext,
                    implode(', ', $allowed),
                ));
            }
        }

        if ($constraint->allowedMimeTypes !== []) {
            $mime = $file->getMimeType();
            $allowed = array_map('strtolower', $constraint->allowedMimeTypes);

            if (!\in_array(strtolower($mime), $allowed, true)) {
                return UploadResult::invalid(sprintf(
                    'File type "%s" is not allowed. Allowed: %s.',
                    $mime,
                    implode(', ', $allowed),
                ));
            }
        }

        return UploadResult::valid();
    }
}
