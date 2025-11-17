<?php
declare(strict_types=1);
namespace App\Lib;

const UPLOAD_DIR = __DIR__ . '/../uploads';
const MAX_UPLOAD_SIZE = 4 * 1024 * 1024; // 4MB
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

function ensureUploadDir() : void {
    if(!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
}

function sanitizeFileName(string $name) : string {
    $name = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $name) ?? 'archivo';
    return trim($name, '_');
}

function storeUploadedFile(array $file) : string {
    ensureUploadDir();

    if(($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new \RuntimeException('Error al subir el archivo.');
    }
    if(($file['size'] ?? 0) > MAX_UPLOAD_SIZE) {
        throw new \RuntimeException('El archivo excede el tamaño permitido.');
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if(!in_array($mime, ALLOWED_MIME_TYPES, true)) {
        throw new \RuntimeException('Formato de imagen no permitido.');
    }

    $extension = match ($mime) {
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/webp' => '.webp',
        default => '.bin',
    };

    $baseName = sanitizeFileName(pathinfo($file['name'] ?? 'imagen', PATHINFO_FILENAME));
    $fileName = $baseName !== '' ? $baseName : 'imagen';
    $finalName = $fileName . '-' . uniqid() . $extension;
    $targetPath = UPLOAD_DIR . '/' . $finalName;

    if(!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new \RuntimeException('No se pudo guardar el archivo en el servidor.');
    }

    return 'uploads/' . $finalName;
}

function deleteStoredFile(?string $path) : void {
    if ($path === null || $path === '') {
        return;
    }

    $fullPath = __DIR__ . '/../' . ltrim($path, '/');
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}
