<?php

declare(strict_types=1);

namespace App\Filters;

use Config\Services;
use dcardenasl\Ci4ApiCore\Exceptions\ApiException;
use dcardenasl\Ci4ApiCore\Http\Filters\AbstractJwtAuthFilter;
use stdClass;

/** Loads the Hub's cross-application effective permission projection. */
final class EffectivePermissionsAuthFilter extends AbstractJwtAuthFilter
{
    protected function decodeToken(string $token): ?object
    {
        try {
            $user = Services::hubClient()->getAuthenticatedUser($token);
        } catch (ApiException) {
            return null;
        } catch (\Throwable $exception) {
            log_message('warning', 'Cross-application auth context unavailable: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        $userId = $user['id'] ?? null;
        $rawPermissions = $user['permissions'] ?? null;
        if (! is_numeric($userId) || (int) $userId <= 0 || ! is_array($rawPermissions)) {
            return null;
        }

        $permissions = [];
        foreach ($rawPermissions as $permission) {
            if (! is_string($permission) || trim($permission) === '') {
                return null;
            }
            $permissions[trim($permission)] = true;
        }

        $decoded = new stdClass();
        $decoded->uid = (int) $userId;
        $decoded->scope = array_keys($permissions);
        $decoded->app_id = null;
        $decoded->jti = null;

        return $decoded;
    }
}
