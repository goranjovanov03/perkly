<?php

use App\Services\AuditLogger;

function audit(string $action, ?string $resourceType = null, ?string $resourceId = null, array $metadata = []): void
{
    app(AuditLogger::class)->record($action, $resourceType, $resourceId, $metadata);
}