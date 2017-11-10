<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\DictionaryCollection;
use SpellChecker\Word;

class DictionarySearch implements \SpellChecker\Heuristic\Heuristic
{

    /** @var \SpellChecker\DictionaryCollection */
    private $dictionaries;

    public function __construct(DictionaryCollection $dictionaries)
    {
        $this->dictionaries = $dictionaries;
    }

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        if ($this->dictionaries->contains($word->word, $dictionaries)) {
            return true;
        }
        if ($this->dictionaries->contains(mb_strtolower($word->word), $dictionaries)) {
            return true;
        }
        if ($word->block !== null && $this->dictionaries->contains($word->block, $dictionaries)) {
            return true;
        }

        $trimmed = $this->trimNumbersFromRight($word->word);
        if ($trimmed !== null) {
            if ($this->dictionaries->contains($trimmed, $dictionaries)) {
                return true;
            }
            if ($this->dictionaries->contains(mb_strtolower($trimmed), $dictionaries)) {
                return true;
            }
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
