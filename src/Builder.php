<?php

declare(strict_types=1);

namespace webignition\SingleCommandApplicationPharBuilder;

use Iterator;
use Phar;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class Builder
{
    private string $baseDirectory;
    private string $pharPath;
    private string $alias;
    private string $binPath;

    /**
     * @var string[]
     */
    private array $sourcePaths;

    /**
     * @param string[] $sourcePaths
     */
    public function __construct(string $baseDirectory, string $pharPath, string $binPath, array $sourcePaths)
    {
        $this->baseDirectory = $baseDirectory;
        $this->pharPath = $pharPath;
        $this->alias = basename($pharPath);
        $this->binPath = $binPath;
        $this->sourcePaths = $sourcePaths;
    }

    public function build(): string
    {
        $phar = new Phar($this->pharPath, 0, $this->alias);
        $phar->startBuffering();

        $this->addBinCompiler($phar);

        $phar->buildFromIterator(
            $this->createFilesFinder($this->sourcePaths),
            $this->baseDirectory
        );

        $phar->addFile('vendor/autoload.php');

        $phar->setStub($this->createStub());
        $phar->stopBuffering();

        return (string) realpath($this->baseDirectory . '/' . $this->pharPath);
    }

    private function addBinCompiler(Phar $phar): void
    {
        $content = (string) file_get_contents($this->baseDirectory . '/' . $this->binPath);
        $content = (string) preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString($this->binPath, $content);
    }

    /**
     * @param string[] $paths
     *
     * @return Iterator<SplFileInfo>
     */
    private function createFilesFinder(array $paths): Iterator
    {
        $finder = new Finder();
        $finder
            ->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('Tests')
            ->exclude('tests')
            ->exclude('docs')
            ->in($paths)
        ;

        return $finder->getIterator();
    }

    private function createStub(): string
    {
        return <<<EOT
#!/usr/bin/env php
<?php

Phar::mapPhar('{$this->alias}');

require 'phar://{$this->alias}/{$this->binPath}';

__HALT_COMPILER();

EOT;
    }
}
