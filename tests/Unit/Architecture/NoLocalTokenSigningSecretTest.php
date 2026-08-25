<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use CodeIgniter\Test\CIUnitTestCase;

final class NoLocalTokenSigningSecretTest extends CIUnitTestCase
{
    public function testBffDoesNotConfigureOrGenerateAnUpstreamSigningSecret(): void
    {
        $root = rtrim((string) ROOTPATH, DIRECTORY_SEPARATOR);
        $files = [
            $root . '/app/Config',
            $root . '/app/Commands',
            $root . '/scripts',
        ];
        $contents = '';

        foreach ($files as $path) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile()) {
                    $contents .= (string) file_get_contents($file->getPathname());
                }
            }
        }

        $this->assertStringNotContainsString('JWT_SECRET_KEY', $contents);
    }
}
