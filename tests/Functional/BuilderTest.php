<?php

declare(strict_types=1);

namespace webignition\BasilCliCompiler\Tests\Functional\Command;

use PHPUnit\Framework\TestCase;
use webignition\SingleCommandApplicationPharBuilder\Builder;

class BuilderTest extends TestCase
{
    public function testBuilder(): void
    {
        $baseDirectory = (string) realpath(__DIR__ . '/../..');
        $pharPath = 'tests/build/app.phar';
        $binPath = 'tests/bin/example-application';
        $sourcePaths = [
            'src',
            'vendor',
        ];

        $builder = new Builder($baseDirectory, $pharPath, $binPath, $sourcePaths);
        $builtPharPath = $builder->build();

        self::assertEquals(
            'Example Application Output',
            (string) shell_exec('php ' . $builtPharPath)
        );

        if (file_exists($builtPharPath)) {
            unlink($builtPharPath);
        }
    }
}
