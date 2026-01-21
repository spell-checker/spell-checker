<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Application\Configurator;
use Dogma\ArrayIterator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;
use function array_intersect;
use function array_keys;
use function array_map;
use function getcwd;
use function iterator_to_array;
use function str_replace;
use function strlen;
use function trim;

class FileFinder
{

    private string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir !== null ? $this->fixPath($baseDir) : $this->fixPath(getcwd());
        if ($this->baseDir !== '') {
            $this->baseDir .= '/';
        }
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }

    /**
     * @return string[]
     */
    public function findFilesByConfig(Configurator $config): array
    {
        return $this->filterFiles(
            $config->files ?? [],
            $config->directories ?? [],
            $config->extensions ?? []
        );
    }

    /**
     * @param string[]|null $directories
     * @param string[]|null $extensions
     * @param string[]|null $excludes
     * @return string[]
     */
    public function findFiles(?array $directories, ?array $extensions = [], ?array $excludes = []): array
    {
        return $this->filterFiles([], $directories, $extensions, $excludes);
    }

    /**
     * @param string[]|null $files
     * @param string[]|null $directories
     * @param string[]|null $extensions
     * @param string[]|null $excludes
     * @return string[]
     */
    public function filterFiles(?array $files, ?array $directories, ?array $extensions = [], ?array $excludes = []): array
    {
        $recursive = [];
        $nonRecursive = [];
        if ($directories) {
            foreach ($directories as $directory) {
                $directory = $this->fixPath($directory);
                if ($directory[strlen($directory) - 1] === '/' && strlen($directory) > 1) {
                    $nonRecursive[] = $this->baseDir . trim($directory, '/');
                } else {
                    $recursive[] = $this->baseDir . trim($directory, '/');
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
                return array_intersect($foundFiles, $this->fixPaths($files, $this->baseDir));
            } else {
                return $foundFiles;
            }
        } elseif ($files) {
            if ($this->baseDir !== '') {
                $foundFiles = array_map(function (string $name): string {
                    return $this->fixPath($this->baseDir . $name);
                }, $files);
            } else {
                $foundFiles = $this->fixPaths($files);
            }

            // filter files by extensions and excludes
            if ($extensions || $excludes) {
                return array_keys(iterator_to_array(new FilenameFilterIterator(new ArrayIterator($foundFiles), $extensions ?? [], $excludes ?? [])));
            } else {
                return $foundFiles;
            }
        } else {
            throw new FileSearchNotConfiguredException();
        }
    }

    /**
     * @param string[]|null $extensions
     * @param string[]|null $excludes
     * @return Finder
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
        return str_replace('\\', '/', $path);
    }

    /**
     * @param string[] $paths
     * @return string[]
     */
    public function fixPaths(array $paths, string $baseDir = ''): array
    {
        return array_map(function (string $path) use ($baseDir) {
            return $baseDir . $this->fixPath($path);
        }, $paths);
    }

}
