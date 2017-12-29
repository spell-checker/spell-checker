<?php declare(strict_types = 1);

namespace SpellChecker\Dictionary;

class DictionaryResolver
{

    private const SKIP_FILE_CONTEXT = 'skip';

    /** @var string[] */
    private $filePatterns = [];

    /** @var string[][] */
    private $contexts;

    /** @var string[][] */
    private $extensions;

    /**
     * @param string[] $filePatterns
     * @param string[][] $contexts
     * @param string[][] $dictionariesByFileExtension
     */
    public function __construct(array $filePatterns, array $contexts, array $dictionariesByFileExtension)
    {
        $this->setPatterns($filePatterns);
        $this->contexts = $contexts;
        $this->extensions = $dictionariesByFileExtension;
    }

    private function setPatterns(array $patterns): void
    {
        foreach ($patterns as $pattern => $context) {
            $this->filePatterns['/^' . str_replace(['.', '*', '?', '/'], ['\\.', '.*', '.', '\\/'], $pattern) . '$/'] = $context;
        }
    }

    /**
     * @param string $fileName
     * @return string[]
     */
    public function getDictionariesForFileName(string $fileName): array
    {
        $context = $this->getContextForFileName($fileName);
        if ($context === self::SKIP_FILE_CONTEXT) {
            return [];
        }

        $dictionaries = [];
        if ($context !== null) {
            $dictionaries = $this->contexts[$context] ?? [];
        }

        $parts = explode('.', $fileName);
        $extension = end($parts);
        if (isset($this->extensions[$extension])) {
            $dictionaries = array_merge($dictionaries, $this->extensions[$extension]);
        }

        return $dictionaries;
    }

    public function getContextForFileName(string $fileName): ?string
    {
        if (strpos($fileName, '.') === false) {
            return null;
        }

        foreach ($this->filePatterns as $pattern => $context) {
            if (preg_match($pattern, $fileName)) {
                return $context;
            }
        }

        return null;
    }

}
