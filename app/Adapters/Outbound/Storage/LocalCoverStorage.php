<?php

namespace App\Adapters\Outbound\Storage;

use App\Application\Ports\CoverStorageInterface;
use Illuminate\Support\Str;

final class LocalCoverStorage implements CoverStorageInterface
{
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, '/\\');
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function store(string $binaryData, string $mimeType): string
    {
        $key = (string)Str::uuid();
        $ext = ($mimeType === 'image/png') ? 'png' : 'jpg';
        $filename = "{$key}.{$ext}";
        $filePath = "{$this->storageDir}/{$filename}";

        file_put_contents($filePath, $binaryData);

        // Guardamos metadata de mime si se desea
        file_put_contents("{$this->storageDir}/{$key}.meta", $mimeType);

        return $key;
    }

    public function get(string $coverKey): ?array
    {
        $metaPath = "{$this->storageDir}/{$coverKey}.meta";
        $mime = file_exists($metaPath) ? trim(file_get_contents($metaPath)) : 'image/jpeg';

        $ext = ($mime === 'image/png') ? 'png' : 'jpg';
        $filePath = "{$this->storageDir}/{$coverKey}.{$ext}";

        if (!file_exists($filePath)) {
            return null;
        }

        return [
            'content' => file_get_contents($filePath),
            'mime' => $mime,
        ];
    }

    public function delete(string $coverKey): void
    {
        foreach (['jpg', 'png', 'meta'] as $ext) {
            $p = "{$this->storageDir}/{$coverKey}.{$ext}";
            if (file_exists($p)) {
                @unlink($p);
            }
        }
    }
}
