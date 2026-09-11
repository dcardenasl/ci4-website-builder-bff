<?php

declare(strict_types=1);

namespace App\Support\Sql;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseResult;
use RuntimeException;

/**
 * Fail-closed helpers for bounded read-only SQL projections.
 *
 * This is a standalone utility, not an AdminRead module. Callers must supply
 * source-owned SQL/table names and keep values in the bindings array.
 */
final class ReadOnlyQuery
{
    /** @return list<array<string, mixed>> */
    public static function rows(BaseBuilder $builder, string $label): array
    {
        $result = $builder->get();
        if ($result === false) {
            throw new RuntimeException(sprintf('Read-only query failed for %s.', $label));
        }

        return array_values($result->getResultArray());
    }

    public static function count(BaseBuilder $builder, string $label): int
    {
        $rows = self::rows($builder->select('COUNT(*) AS total', false), $label);
        if (! isset($rows[0]['total'])) {
            throw new RuntimeException(sprintf('Read-only count missing for %s.', $label));
        }

        return (int) $rows[0]['total'];
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     * @param list<mixed> $bindings
     * @return list<array<string, mixed>>
     */
    public static function sql(BaseConnection $db, string $sql, array $bindings, string $label): array
    {
        $result = $db->query($sql, $bindings);
        if (! $result instanceof BaseResult) {
            throw new RuntimeException(sprintf('Read-only query failed for %s.', $label));
        }

        return array_values($result->getResultArray());
    }

    /**
     * @param BaseConnection<mixed, mixed> $db
     * @param list<mixed> $bindings
     * @return array{rows: list<array<string, mixed>>, duration_ms: float}
     */
    public static function timedSql(BaseConnection $db, string $sql, array $bindings, string $label): array
    {
        $startedAt = hrtime(true);
        $rows = self::sql($db, $sql, $bindings, $label);

        return [
            'rows' => $rows,
            'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
        ];
    }
}
