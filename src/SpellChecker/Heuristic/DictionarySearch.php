<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\Word;

class DictionarySearch implements \SpellChecker\Heuristic\Heuristic
{

    public const TRY_LOWERCASE = 1;
    public const TRY_CAPITALIZED = 2;
    public const TRY_WITHOUT_DIACRITICS = 4;

    /** @var \SpellChecker\Dictionary\DictionaryCollection */
    private $dictionaries;

    public function __construct(DictionaryCollection $dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        if ($this->dictionaries->contains($dictionaries, $word->word, $word->context, self::TRY_LOWERCASE)) {
            return true;
        }
        if ($word->block !== null && $this->dictionaries->contains($dictionaries, $word->block, $word->context)) {
            return true;
        }

        $trimmed = $this->trimNumbersFromRight($word->word);
        if ($trimmed !== null && $this->dictionaries->contains($dictionaries, $trimmed, $word->context, self::TRY_LOWERCASE)) {
            return true;
        }

        return false;
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
