<?php

declare(strict_types=1);

namespace Src\Contexts\Installer\Presentation\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\PathTraversalDetected;
use Symfony\Component\HttpFoundation\Response;

/**
 * بديل `public/storage` على استضافة قافلة `symlink()`.
 *
 * بيقدّم ملفات الديسك العام بس — نفس اللي الـ symlink كان هيكشفه، مش
 * أكتر. الملفات المخفية (`.htaccess`، `.gitignore`) ممنوعة، والصعود في
 * المسار بيرجّع 404 (Flysystem بيرفضه كمان). `sandbox` في CSP عشان أي
 * ملف HTML/SVG اترفع غلط مايشتغلش كصفحة من نفس الدومين.
 */
final class ServePublicFileController
{
    public function __invoke(string $path): Response
    {
        if ($path === '' || str_contains($path, '..') || preg_match('#(^|/)\.#', $path) === 1) {
            abort(404);
        }

        $disk = Storage::disk((string) config('installer.public_files.disk'));

        try {
            abort_unless($disk->fileExists($path), 404);
        } catch (PathTraversalDetected) {
            abort(404);
        }

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age='.(int) config('installer.public_files.max_age'),
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
