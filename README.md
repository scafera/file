# scafera/file

File handling for the Scafera framework. Upload, validate, store, and serve files — all behind Scafera-owned types.

Internally adopts `symfony/http-foundation` Upload, `symfony/mime`, and `symfony/filesystem`. Userland code never imports Symfony file types — boundary enforcement blocks it at compile time.

> **Provides:** File handling for Scafera — upload (`UploadExtractor` → `UploadedFile`), validate (`UploadValidator` + `UploadConstraint`), store (`FileStorage`), and serve (`FileResponse`) behind Scafera-owned types. Server-side MIME detection via magic bytes, automatic path-traversal sanitization, and collision-safe filenames.
>
> **Depends on:** A Scafera host project whose architecture package defines a storage directory via `getStorageDir()` (e.g. `var/uploads/` in `scafera/layered`).
>
> **Extension points:** None of its own — the package exposes concrete services (`UploadExtractor`, `UploadValidator`, `FileStorage`, `FileResponse`) used directly. `UploadConstraint` is configured per-call with allowed extensions, MIME types, and max size.
>
> **Not responsible for:** Form processing (compose with `scafera/form` per ADR-062) · client-supplied MIME types (server-side magic bytes only) · choosing the storage directory (architecture package owns `getStorageDir()`) · direct use of Symfony file types in userland (blocked by `FileBoundaryPass` and `FileBoundaryValidator`).

This is a **capability package**. It adds optional file handling to a Scafera project. It does not define folder structure or architectural rules — those belong to architecture packages.

## What it provides

- `UploadedFile` — file metadata (name, extension, MIME, size) with server-side MIME detection
- `UploadExtractor` — extract uploads from requests
- `UploadConstraint` + `UploadValidator` — validate files (extension, MIME, size)
- `FileStorage` — store files with path traversal protection and collision handling
- `FileResponse` — serve files as download or inline

## Design decisions

- **MIME detection is server-side** — uses `symfony/mime` magic bytes, not the client-supplied Content-Type header. Prevents spoofed file types.
- **Path traversal is structurally prevented** — directory segments are sanitized (stripped of `..` and `.`) before any filesystem operation. No file is ever written before validation.
- **File collision handling** — if a file with the same name exists, a unique random suffix is appended automatically. No silent overwrite.
- **Storage path is architecture-owned** — the storage directory is defined by `getStorageDir()` on the architecture package, not hardcoded. For `scafera/layered`, this is `var/uploads/`.
- **Form and file are separate capabilities** — file uploads are not handled by `scafera/form`. Controllers compose both packages explicitly (ADR-062).

## Installation

```bash
composer require scafera/file
```

## Requirements

- PHP >= 8.4
- scafera/kernel

## Upload and validate

```php
use Scafera\File\UploadExtractor;
use Scafera\File\UploadValidator;
use Scafera\File\UploadConstraint;

$file = $uploads->get($request, 'avatar');

if ($file !== null) {
    $result = $validator->validate($file, new UploadConstraint(
        allowedExtensions: ['jpg', 'png'],
        allowedMimeTypes: ['image/jpeg', 'image/png'],
        maxSizeBytes: 2_097_152,  // 2 MB
    ));

    if (!$result->isValid()) {
        // $result->error() — human-readable message
    }
}
```

MIME detection uses `symfony/mime` magic bytes — not the client-supplied MIME type.

## Store

```php
use Scafera\File\FileStorage;

$path = $storage->store($file, 'avatars');           // 'avatars/photo.jpg'
$path = $storage->store($file, 'avatars', 'me.jpg'); // 'avatars/me.jpg'

$storage->exists($path);   // true
$storage->delete($path);   // true
```

The storage directory is defined by the architecture package via `getStorageDir()`. For `scafera/layered`, this is `var/uploads/`. Filenames are sanitized (directory components stripped). If a file with the same name already exists, a unique suffix is appended automatically.

Path traversal is structurally prevented — directory segments are sanitized before any filesystem operation.

## Serve files

```php
use Scafera\File\FileResponse;

return FileResponse::download($path, 'report.pdf');
return FileResponse::inline($path);
```

`FileResponse` does not implement `ResponseInterface`. A dedicated listener converts it to a binary response at priority 10 (before the kernel's response listener).

## File uploads in forms

File uploads are **not** handled by `scafera/form`. Use both packages together in your controller (ADR-062):

```php
$form = $this->formHandler->handle($request, ProfileInput::class);
$avatar = $this->uploads->get($request, 'avatar');
```

Two explicit calls. Form handles POST data, file handles uploads. Each validates independently.

## Boundary enforcement

| Blocked | Use instead |
|---------|-------------|
| `Symfony\Component\HttpFoundation\File\UploadedFile` | `Scafera\File\UploadedFile` |
| `Symfony\Component\HttpFoundation\File\File` | `Scafera\File\FileStorage` |
| `Symfony\Component\Filesystem\*` | `Scafera\File\FileStorage` |

Enforced via compiler pass (build time) and validator (`scafera validate`). Detects `use`, `new`, and `extends` patterns.

## License

MIT
