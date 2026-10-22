<?php

declare(strict_types=1);

namespace Lvt;

final class Uploader
{
    public static function storeImage(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new HttpException(422, 'The photo did not upload. Try a smaller JPEG or PNG.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            throw new HttpException(422, 'Photos must be 5 MB or smaller.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($map[$mime])) {
            throw new HttpException(422, 'Upload a JPEG, PNG, or WebP photo.');
        }
        $dir = LVT_ROOT . '/storage/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0770, true);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $map[$mime];
        $dest = $dir . '/' . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            if (!rename($tmp, $dest) && !copy($tmp, $dest)) {
                throw new HttpException(422, 'The photo could not be saved.');
            }
        }
        return $name;
    }
}
