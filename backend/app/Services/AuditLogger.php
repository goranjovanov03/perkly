<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class AuditLogger
{
    public function record(string $action, ?string $resourceType = null, ?string $resourceId = null, array $metadata = []): void
    {
        $request = App::make(Request::class);

        $actorId = $request->user()?->id;
        $organizationId = $request->get('current_organization')?->id;
        $requestId = $request->header('X-Request-Id');

        $sanitizedMetadata = $this->sanitizeMetadata($metadata);

        AuditLog::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'actor_id' => $actorId,
            'organization_id' => $organizationId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => $sanitizedMetadata,
            'request_id' => $requestId,
        ]);
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $forbiddenKeys = ['password', 'token', 'access_token', 'refresh_token', 'api_token', 'secret'];

        foreach ($metadata as $key => $value) {
            if (in_array(strtolower($key), $forbiddenKeys)) {
                $metadata[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $metadata[$key] = $this->sanitizeMetadata($value);
            }
        }

        return $metadata;
    }
}