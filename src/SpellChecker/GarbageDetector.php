<?php declare(strict_types = 1);

namespace SpellChecker;

class GarbageDetector
{

    /**
     * Guesses if a word may be a token or part of base64 encoded string to filter them from results
     * @param string $string
     * @return bool
     */
    public function looksLikeGarbage(string $string): bool
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
