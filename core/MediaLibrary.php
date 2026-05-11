<?php

declare(strict_types=1);

final class MediaLibrary
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function all(): array
    {
        $this->ensureUploadDirectory();
        $files = glob($this->uploadDirectory() . '/*') ?: [];
        $media = [];

        foreach ($files as $file) {
            if (!is_file($file) || str_starts_with(basename($file), '.')) {
                continue;
            }

            $media[] = [
                'name' => basename($file),
                'url' => $this->urlFor(basename($file)),
                'size' => filesize($file) ?: 0,
                'modified' => date('Y-m-d H:i:s', filemtime($file) ?: time()),
            ];
        }

        usort($media, fn (array $a, array $b): int => strcmp($b['modified'], $a['modified']));

        return $media;
    }

    public function upload(array $file): array
    {
        $this->ensureUploadDirectory();

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed. Please choose an image file and try again.');
        }

        $tmpName = (string)($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Upload failed validation.');
        }

        $mimeType = mime_content_type($tmpName) ?: '';
        if (!isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new RuntimeException('Only JPG, PNG, GIF, and WebP images are allowed.');
        }

        $originalName = pathinfo((string)($file['name'] ?? 'image'), PATHINFO_FILENAME);
        $safeName = $this->safeFileName($originalName);
        $extension = self::ALLOWED_MIME_TYPES[$mimeType];
        $fileName = $safeName . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $destination = $this->uploadDirectory() . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            throw new RuntimeException('Could not save the uploaded image.');
        }

        chmod($destination, 0644);

        return [
            'name' => $fileName,
            'url' => $this->urlFor($fileName),
            'size' => filesize($destination) ?: 0,
            'modified' => date('Y-m-d H:i:s'),
        ];
    }

    public function uploadDirectory(): string
    {
        return CMS_ROOT . '/assets/uploads';
    }

    private function ensureUploadDirectory(): void
    {
        if (!is_dir($this->uploadDirectory())) {
            mkdir($this->uploadDirectory(), 0755, true);
        }
    }

    private function urlFor(string $fileName): string
    {
        return cms_base_url('/assets/uploads/' . rawurlencode($fileName));
    }

    private function safeFileName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', '-', $name) ?: 'image';
        $name = trim($name, '-');

        return $name === '' ? 'image' : substr($name, 0, 80);
    }
}
