<?php declare(strict_types = 1);

namespace SpellChecker;

class SpellChecker
{

    /** @var \SpellChecker\WordsParser */
    private $wordsParser;

    /** @var \SpellChecker\GarbageDetector */
    private $garbageDetector;

    /** @var \SpellChecker\DictionaryResolver */
    private $resolver;

    /** @var \SpellChecker\DictionaryCollection */
    private $dictionaries;

    public function __construct(
        WordsParser $wordsParser,
        GarbageDetector $garbageDetector,
        DictionaryResolver $resolver,
        DictionaryCollection $dictionaries
    )
    {
        $this->wordsParser = $wordsParser;
        $this->garbageDetector = $garbageDetector;
        $this->resolver = $resolver;
        $this->dictionaries = $dictionaries;
    }

    /**
     * @param string[] $files
     * @param callable|null (string fileName: bool) $fileCallback
     * @return \SpellChecker\Word[][]
     */
    public function checkFiles(array $files, ?callable $fileCallback = null): array
    {
        $errors = [];
        foreach ($files as $path) {
            if (!is_readable($path)) {
                continue;
            }
            $dictionaries = $this->resolver->getDictionariesForFileName($path);
            if ($dictionaries === []) {
                continue;
            }
            $fileErrors = $this->checkFile($path, $dictionaries, $fileCallback);
            if ($fileErrors !== []) {
                $errors[$path . ' (' . implode(', ', $dictionaries) . ')'] = $fileErrors;
            }
        }

        return $errors;
    }

    /**
     * @param string $fileName
     * @param string[] $dictionaries
     * @param callable|null (string fileName: bool) $fileCallback
     * @return \SpellChecker\Word[]
     */
    private function checkFile(string $fileName, array $dictionaries, ?callable $fileCallback = null): array
    {
        if ($fileCallback !== null) {
            if (!$fileCallback($fileName)) {
                return [];
            }
        }
        ///
        $string = file_get_contents($fileName);
        $string = \Nette\Utils\Strings::normalize($string);
        ///

        return $this->checkString($string, $dictionaries, $fileName);
    }

    /**
     * @param string $string
     * @param string[] $dictionaries
     * @param string $fileName
     * @return \SpellChecker\Word[]
     */
    public function checkString(string $string, array $dictionaries, string $fileName): array
    {
        $errors = [];
        foreach ($this->wordsParser->parse($string) as $word) {
            if ($this->dictionaries->contains($word->word, $dictionaries)) {
                continue;
            }
            if ($this->dictionaries->contains(mb_strtolower($word->word), $dictionaries)) {
                continue;
            }
            if ($word->block !== null && $this->dictionaries->contains($word->block, $dictionaries)) {
                continue;
            }

            $trimmed = $this->trimNumbersFromRight($word->word);
            if ($trimmed !== null) {
                if ($this->dictionaries->contains($trimmed, $dictionaries)) {
                    continue;
                }
                if ($this->dictionaries->contains(mb_strtolower($trimmed), $dictionaries)) {
                    continue;
                }
            }
            if ($word->block !== null && $this->garbageDetector->looksLikeGarbage($word->block)) {
                continue;
            }
            if ($this->garbageDetector->looksLikeGarbage($word->word)) {
                continue;
            }

            $context = substr($string, $word->position - 30, strlen($word->word) + 60);
            while (ord($context[0]) & 0b11000000 === 0b1000000) {
                $context = substr($context, 1);
            }
            $word->context = '…' . preg_replace('/([ ]{2,}|\\t+)/', '→', str_replace("\n", '↓', $context)) . '…';
            $errors[] = $word;
        }

        return $errors;
    }

    private function trimNumbersFromRight(string $word): ?string
    {
        if (preg_match('/[0-9]+$/', $word, $match)) {
            return substr($word, 0, -strlen($match[0]));
        } else {
            return null;
        }
    }

}
