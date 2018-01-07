<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

/**
 * Heuristic to filter some HTML tags sometimes used outside of HTML context. For example in translation strings.
 */
class SimpleHtmlDetector implements \SpellChecker\Heuristic\Heuristic
{

    /** @var string[] */
    private $tags = [
        'a' => true,
        'i' => true,
        'b' => true,
        'br' => true,
        'em' => true,
        'strong' => true,
        'sub' => true,
        'sup' => true,
    ];

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        // <b>, </b>
        if (isset($this->tags[$word->word])) {
            $after = $string[$word->position + 1];
            if ($after !== '>' && $after !== ' ') {
                return false;
            }

            $before = $string[$word->position - 1];
            if (($before === '<' || ($before === '/' && $string[$word->position - 2] === '<'))) {
                return true;
            }
        }

        return false;
    }

}
