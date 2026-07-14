<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owns all manuscript/review/article file I/O. Local disk now
 * (FILESYSTEM_DISK=local -> storage/app/private, never a public disk),
 * swappable to s3/r2 later purely via .env (config/filesystems.php already
 * defines an 's3' disk driven by AWS_* env vars, which R2 is compatible
 * with via AWS_ENDPOINT).
 */
class FileStorageService
{
    private function disk()
    {
        return Storage::disk(config('filesystems.default'));
    }

    public function storeManuscriptFile(int $manuscriptId, int $versionId, UploadedFile $file, string $type = 'supplementary'): string
    {
        $name = $type === 'main' ? 'main.pdf' : $file->hashName();
        $path = "manuscripts/{$manuscriptId}/versions/{$versionId}/".($type === 'main' ? $name : "supplementary/{$name}");

        $this->disk()->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function storeReviewAnnotatedFile(int $reviewId, UploadedFile $file): string
    {
        $name = $file->hashName();
        $path = "reviews/{$reviewId}/annotated/{$name}";
        $this->disk()->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }

    public function storeArticlePdf(int $articleId, string $binaryContents): string
    {
        $path = "articles/{$articleId}/final.pdf";
        $this->disk()->put($path, $binaryContents);

        return $path;
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    /** Streams a stored file without buffering the whole thing in memory. */
    public function stream(string $path, string $downloadName = 'document.pdf', ?string $contentType = null, string $disposition = 'inline'): StreamedResponse
    {
        return $this->disk()->response($path, $downloadName, [
            'Content-Type' => $contentType ?? ($this->disk()->mimeType($path) ?: 'application/pdf'),
        ], $disposition);
    }
}
