<?php

declare(strict_types=1);

final class GitHubUpdater
{
    private const OWNER = 'BishopGreer';
    private const REPO = 'holycross';
    private const BRANCH = 'main';

    public function latestVersion(): string
    {
        $url = $this->rawUrl('core/bootstrap.php');
        $source = $this->downloadText($url);

        if (!preg_match("/const CMS_VERSION = '([^']+)';/", $source, $matches)) {
            throw new RuntimeException('Could not read the latest version from GitHub.');
        }

        return $matches[1];
    }

    public function hasUpdate(string $latestVersion): bool
    {
        return version_compare($latestVersion, CMS_VERSION, '>');
    }

    public function install(string $version): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('PHP ZipArchive is required to install updates from GitHub release files.');
        }

        $zipPath = $this->downloadRelease($version);
        $extractPath = $this->extractRelease($zipPath);
        $copied = $this->copyReleaseFiles($extractPath, CMS_ROOT);
        $migrations = (new Migrator(Database::connect()))->migrate();
        $this->deleteDirectory($extractPath);
        @unlink($zipPath);

        return [
            'files' => $copied,
            'migrations' => $migrations,
        ];
    }

    private function downloadRelease(string $version): string
    {
        $fileName = 'holycross-cms-' . $version . '.zip';
        $url = $this->rawUrl('releases/' . $fileName);
        $target = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
        $data = $this->downloadText($url);

        if (file_put_contents($target, $data, LOCK_EX) === false) {
            throw new RuntimeException('Could not write the downloaded update package.');
        }

        return $target;
    }

    private function extractRelease(string $zipPath): string
    {
        $extractPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'holycross-cms-update-' . bin2hex(random_bytes(6));
        mkdir($extractPath, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Could not open the downloaded update package.');
        }

        if (!$zip->extractTo($extractPath)) {
            $zip->close();
            throw new RuntimeException('Could not extract the downloaded update package.');
        }

        $zip->close();

        return $extractPath;
    }

    private function copyReleaseFiles(string $source, string $destination): int
    {
        $copied = 0;
        $items = scandir($source);

        if ($items === false) {
            throw new RuntimeException('Could not read extracted update files.');
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $item;
            $destinationPath = $destination . DIRECTORY_SEPARATOR . $item;
            $relativePath = ltrim(str_replace(CMS_ROOT, '', $destinationPath), DIRECTORY_SEPARATOR);

            if ($this->shouldSkip($relativePath)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $copied += $this->copyReleaseFiles($sourcePath, $destinationPath);
                continue;
            }

            if (!copy($sourcePath, $destinationPath)) {
                throw new RuntimeException('Could not copy update file: ' . $relativePath);
            }

            $copied++;
        }

        return $copied;
    }

    private function shouldSkip(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', $relativePath);

        return $relativePath === 'config/config.php'
            || str_starts_with($relativePath, '.git/')
            || str_starts_with($relativePath, 'releases/');
    }

    private function downloadText(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'header' => "User-Agent: HolyCrossCMS/" . CMS_VERSION . "\r\n",
                'timeout' => 30,
            ],
        ]);
        $data = @file_get_contents($url, false, $context);

        if ($data === false) {
            throw new RuntimeException('Could not download from GitHub: ' . $url);
        }

        return $data;
    }

    private function rawUrl(string $path): string
    {
        return 'https://raw.githubusercontent.com/' . self::OWNER . '/' . self::REPO . '/' . self::BRANCH . '/' . ltrim($path, '/');
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
            } else {
                @unlink($itemPath);
            }
        }

        @rmdir($path);
    }
}
