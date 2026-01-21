<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;
use function count;
use function preg_replace;
use function preg_split;
use function strlen;

/**
 * Guesses if a word may be a token or password or part of base64 encoded string
 */
class GarbageDetector implements Heuristic
{

    public const string RESULT_GARBAGE = 'garbage';

    /**
     * @param string[] $dictionaries
     * @return string|null
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        if ($word->block !== null && $this->checkWord($word->block)) {
            return self::RESULT_GARBAGE;
        }
        if ($this->checkWord($word->word)) {
            return self::RESULT_GARBAGE;
        }

        return null;
    }

    private function checkWord(string $string): bool
    {
        $length = strlen($string);
        $upper = $length - strlen(preg_replace('/[A-Z]/', '', $string));
        $lower = $length - strlen(preg_replace('/[a-z]/', '', $string));
        $numbers = $length - strlen(preg_replace('/[0-9]/', '', $string));

        if ($upper >= $length / 4 && $lower > 0) {
            return true;
        }
        if ($numbers >= $length / 4) {
            return true;
        }

        return count(preg_split('/[0-9]/', $string)) > 3;
    }

}
