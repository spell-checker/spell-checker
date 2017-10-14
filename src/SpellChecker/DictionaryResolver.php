<?php declare(strict_types = 1);

namespace SpellChecker;

class DictionaryResolver
{

    private const SKIP_FILE_CONTEXT = 'skip';

    /** @var string[] */
    private $filePatterns = [];

    /** @var string[][] */
    private $contexts;

    /**
     * @param string[] $filePatterns
     * @param string[][] $contexts
     */
    public function __construct(array $filePatterns, array $contexts)
    {
        $this->setPatterns($filePatterns);
        $this->contexts = $contexts;
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
        if ($context === null || $context === self::SKIP_FILE_CONTEXT) {
            return [];
        }

        if (!isset($this->contexts[$context])) {
            throw new \SpellChecker\ContextNotDefinedException($context);
        }

        return $this->contexts[$context];
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

    /**
     * @param string $context
     * @return string[]
     */
    public function getDictionariesForContext(string $context): array
    {
        if ($context === self::SKIP_FILE_CONTEXT) {
            return [];
        }
        if (!isset($this->contexts[$context])) {
            throw new \SpellChecker\ContextNotDefinedException($context);
        }

        return $this->contexts[$context];
    }

}
