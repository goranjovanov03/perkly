<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Models\Organization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    use AuthorizesRequests;

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/webp',
    ];

    private const MAX_SIZE = 10 * 1024 * 1024;

    public function store(Request $request): JsonResponse
    {
        $this->authorize('files.upload', Organization::class);

        $organization = $request->get('current_organization');

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $uploadedFile = $request->file('file');

        $realMimeType = $uploadedFile->getMimeType();
        if (! in_array($realMimeType, self::ALLOWED_MIME_TYPES)) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'File type not allowed.',
                    'request_id' => $request->header('X-Request-Id') ?? (string) Str::uuid(),
                    'fields' => [
                        'file' => ['The file type is not allowed. Allowed types: PDF, PNG, JPG, WebP.'],
                    ],
                ],
            ], 422);
        }

        if ($uploadedFile->getSize() > self::MAX_SIZE) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'File size exceeds 10 MB limit.',
                    'request_id' => $request->header('X-Request-Id') ?? (string) Str::uuid(),
                    'fields' => [
                        'file' => ['The file size must not exceed 10 MB.'],
                    ],
                ],
            ], 422);
        }

        $fileId = Str::ulid();
        $extension = $this->getExtension($realMimeType);
        $storedPath = "organizations/{$organization->id}/{$fileId}.{$extension}";

        $disk = Storage::disk('private');
        $disk->put($storedPath, file_get_contents($uploadedFile));

        $sha256 = hash_file('sha256', $disk->path($storedPath));

        $file = File::create([
            'id' => $fileId,
            'organization_id' => $organization->id,
            'uploader_id' => $request->user()->id,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $realMimeType,
            'size_bytes' => $uploadedFile->getSize(),
            'sha256_hash' => $sha256,
        ]);

        audit('file.uploaded', 'file', $file->id, [
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
        ]);

        return response()->json([
            'file' => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size_bytes' => $file->size_bytes,
                'sha256_hash' => $file->sha256_hash,
                'created_at' => $file->created_at,
            ],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('files.view', Organization::class);

        $organization = $request->get('current_organization');

        $files = File::where('organization_id', $organization->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size_bytes' => $file->size_bytes,
                    'sha256_hash' => $file->sha256_hash,
                    'created_at' => $file->created_at,
                    'uploader' => [
                        'id' => $file->uploader->id,
                        'name' => $file->uploader->name,
                    ],
                ];
            });

        return response()->json(['files' => $files]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('files.view', Organization::class);

        $organization = $request->get('current_organization');

        $file = File::where('organization_id', $organization->id)
            ->where('id', $id)
            ->with('uploader')
            ->firstOrFail();

        $downloadUrl = $this->generateDownloadUrl($file);

        return response()->json([
            'file' => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size_bytes' => $file->size_bytes,
                'sha256_hash' => $file->sha256_hash,
                'created_at' => $file->created_at,
                'uploader' => [
                    'id' => $file->uploader->id,
                    'name' => $file->uploader->name,
                ],
                'download_url' => $downloadUrl,
            ],
        ]);
    }

    public function download(Request $request, string $id): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $file = File::where('id', $id)->firstOrFail();

        $disk = Storage::disk('private');
        $path = $disk->path($file->stored_path);

        if (! $disk->exists($file->stored_path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"',
        ]);
    }

    private function generateDownloadUrl(File $file): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'files.download',
            now()->addMinutes(5),
            ['file' => $file->id]
        );
    }

    private function getExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }
}