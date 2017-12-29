<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

class GarbageDetector implements \SpellChecker\Heuristic\Heuristic
{

    /**
     * Guesses if a word may be a token or part of base64 encoded string to filter them from results
     * @param \SpellChecker\Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return bool
     */
    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        if ($word->block !== null && $this->checkWord($word->block)) {
            return true;
        }
        if ($this->checkWord($word->word)) {
            return true;
        }
        return false;
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
        if (count(preg_split('/[0-9]/', $string)) > 3) {
            return true;
        }
        return false;
    }

}
