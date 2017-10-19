<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Tools\Configurator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;

class FileFinder
{

    /**
     * @param \Dogma\Tools\Configurator $config
     * @return string[]
     */
    public function findFilesByConfig(Configurator $config): array
    {
        return $this->filterFiles($config->files, $config->directories, $config->extensions, $config->excludes, $config->baseDir);
    }

    /**
     * @param string[]|null $directories
     * @param string[]|null $extensions
     * @param string[]|null $excludes
     * @param string|null $baseDir
     * @return string[]
     */
    public function findFiles(?array $directories, ?array $extensions = [], ?array $excludes = [], ?string $baseDir = null): array
    {
        return $this->filterFiles([], $directories, $extensions, $excludes, $baseDir);
    }

    /**
     * @param string[]|null $files
     * @param string[]|null $directories
     * @param string[]|null $extensions
     * @param string[]|null $excludes
     * @param string|null $baseDir
     * @return string[]
     */
    public function filterFiles(?array $files, ?array $directories, ?array $extensions = [], ?array $excludes = [], ?string $baseDir = null): array
    {
        $baseDir = $baseDir !== null ? $this->fixPath($baseDir) : $this->fixPath(getcwd());
        if ($baseDir !== '') {
            $baseDir .= '/';
        }
        $recursive = [];
        $nonRecursive = [];
        if ($directories) {
            foreach ($directories as $directory) {
                $directory = $this->fixPath($directory);
                if ($directory[strlen($directory) - 1] === '/' && strlen($directory) > 1) {
                    $nonRecursive[] = $baseDir . trim($directory, '/');
                } else {
                    $recursive[] = $baseDir . trim($directory, '/');
                }
            }
        }

        if ($directories) {
            // find files
            $foundFiles = $this->createFinder($extensions, $excludes);
            $foundFiles->in($recursive);

            if ($nonRecursive !== []) {
                $finder = $this->createFinder($extensions, $excludes);
                $finder->depth(0);
                $finder->in($nonRecursive);
                $foundFiles = $foundFiles->append($finder->getIterator());
            }
            $foundFiles = $this->fixPaths(array_keys(iterator_to_array($foundFiles->getIterator())));

            if ($files) {
                // filter found files by given files (to keep all other constraints)
                return array_intersect($foundFiles, $this->fixPaths($files));
            } else {
                return $foundFiles;
            }

        } elseif ($files) {
            if ($baseDir !== '') {
                $foundFiles = array_map(function (string $name) use ($baseDir): string {
                    return $this->fixPath($baseDir . $name);
                }, $files);
            } else {
                $foundFiles = $this->fixPaths($files);
            }

            // filter files by extensions and excludes
            if ($extensions || $excludes) {
                return iterator_to_array(new FilenameFilterIterator(new \ArrayIterator($foundFiles), $extensions ?? [], $excludes ?? []));
            } else {
                return $foundFiles;
            }

        } else {
            throw new \SpellChecker\FileSearchNotConfiguredException();
        }
    }

    /**
     * @param string[]|null $extensions
     * @param string[]|null $excludes
     * @return \Symfony\Component\Finder\Finder
     */
    private function createFinder(?array $extensions = null, ?array $excludes = null): Finder
    {
        $finder = new Finder();
        $finder->files();
        $finder->ignoreDotFiles(true);
        $finder->ignoreVCS(true);

        if ($extensions) {
            foreach ($extensions as $extension) {
                $finder->name($extension);
            }
        }

        if ($excludes) {
            foreach ($excludes as $exclude) {
                $finder->notName($exclude);
            }
        }

        return $finder;
    }

    public function fixPath(string $path): string
    {
        return strtr($path, '\\', '/');
    }

    /**
     * @param string[] $paths
     * @return string[]
     */
    public function fixPaths(array $paths): array
    {
        return array_map(function (string $path) {
            return $this->fixPath($path);
        }, $paths);
    }

}
