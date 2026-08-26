<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Guardrail that keeps the BFF model-free and stateless.
 *
 * The only permitted database seam is the opt-in, read-only
 * app/PublicRead connection. Resource-specific readers are intentionally not
 * part of this starter until a real endpoint needs them.
 */
final class StatelessArchitectureTest extends CIUnitTestCase
{
    /** @var array<string, string> */
    private const FORBIDDEN_PATTERNS = [
        'use_model' => '/^use\s+App\\\\Models\\\\/m',
        'extends_model' => '/extends\s+(?:\\\\CodeIgniter\\\\)?Model\b/',
        'model_helper' => '/\bmodel\s*\(/',
        'db_connect' => '/\\\\?Database\s*::\s*connect\s*\(/',
        'db_config' => '/^use\s+Config\\\\Database\b/m',
        'db_connection' => '/^use\s+CodeIgniter\\\\Database\\\\/m',
        'write_query' => '/->\s*(?:insert|update|delete|replace|truncate)\s*\(/i',
    ];

    public function testCodebaseIsCompletelyStateless(): void
    {
        $root = rtrim((string) ROOTPATH, DIRECTORY_SEPARATOR);
        $appDir = $root . DIRECTORY_SEPARATOR . 'app';
        $violations = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($appDir));

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || ! $file->isFile() || ! str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $path = $file->getPathname();
            $relative = str_replace('\\', '/', ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR));

            // Framework migration/configuration files are not application state
            // boundaries and are outside this guard's scope.
            if (str_starts_with($relative, 'app/Database/') || $relative === 'app/Config/Database.php') {
                continue;
            }

            $readDatabaseSeam = str_starts_with($relative, 'app/PublicRead/');
            $source = file_get_contents($path);
            if (! is_string($source) || $source === '') {
                continue;
            }

            // Strip comments and string literals to avoid matching examples or
            // documentation instead of executable PHP.
            $code = '';
            foreach (token_get_all($source) as $token) {
                if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true)) {
                    $code .= str_repeat("\n", substr_count($token[1], "\n"));
                    continue;
                }

                $code .= is_array($token) ? $token[1] : $token;
            }

            foreach (self::FORBIDDEN_PATTERNS as $ruleName => $pattern) {
                if ($readDatabaseSeam && in_array($ruleName, ['db_connect', 'db_config', 'db_connection'], true)) {
                    continue;
                }
                if (! $readDatabaseSeam && $ruleName === 'write_query') {
                    continue;
                }

                $count = preg_match_all($pattern, $code);
                if ($count > 0) {
                    $violations[] = "{$relative}: violating '{$ruleName}' (matched {$count} times)";
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Stateless architecture violations found in ci4-bff-starter:\n- " . implode("\n- ", $violations) . "\n\n"
            . 'The BFF must remain model-free; direct reads are permitted only through the isolated app/PublicRead read-only seam.'
        );
    }
}
