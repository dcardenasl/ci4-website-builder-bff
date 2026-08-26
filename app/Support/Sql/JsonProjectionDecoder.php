<?php

declare(strict_types=1);

namespace App\Support\Sql;

/** Decodes a JSON array projection into a safe list of associative rows. */
final class JsonProjectionDecoder
{
    /** @return list<array<string, mixed>> */
    public static function decodeList(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_array'));
    }
}
