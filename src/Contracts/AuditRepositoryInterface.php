<?php

namespace Vima\Core\Contracts;

/**
 * Interface AuditRepositoryInterface
 */
use Vima\Core\Entities\Bare\BareAuditLog;

/**
 * Interface AuditRepositoryInterface
 */
interface AuditRepositoryInterface
{
    /**
     * Log an access check.
     *
     * @param BareAuditLog|array $data
     * @return void
     */
    public function log(BareAuditLog|array $data): void;
}
