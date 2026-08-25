<?php

declare(strict_types=1);

namespace App\Filters;

use dcardenasl\Ci4ApiCore\Http\Filters\AbstractWebAppKeyRequiredFilter;

/** Fail-closed gate for opt-in server-to-server public-read routes. */
final class WebAppKeyRequiredFilter extends AbstractWebAppKeyRequiredFilter
{
    protected function webAppKey(): string
    {
        /** @var \Config\Bff $config */
        $config = config('Bff');

        return $config->webAppKey;
    }
}
