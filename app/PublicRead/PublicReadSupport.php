<?php

declare(strict_types=1);

namespace App\PublicRead;

use CodeIgniter\Database\BaseConnection;
use Config\Database;
use LogicException;

/**
 * Explicit seam for optional direct reads from a domain's read-only database.
 *
 * It is disabled by default and has no resource-specific queries. A future
 * site may opt in and add readers against the generic connection contract;
 * the BFF never silently falls back to direct SQL.
 */
final class PublicReadSupport
{
    public function isEnabled(): bool
    {
        return (bool) config('Bff')->publicReadEnabled;
    }

    /** @return BaseConnection<mixed, mixed> */
    public function connection(): BaseConnection
    {
        if (! $this->isEnabled()) {
            throw new LogicException('Direct public-read support is disabled.');
        }

        /** @var BaseConnection<mixed, mixed> $connection */
        $connection = Database::connect('public_readonly');

        return $connection;
    }
}
