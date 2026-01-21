<?php declare(strict_types = 1);
// spell-check-ignore: br

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

/**
 * Heuristic to filter some HTML tags sometimes used outside of HTML context. For example in translation strings.
 */
class SimpleHtmlDetector implements Heuristic
{

    public const string RESULT_HTML = 'html';

    /** @var bool[] */
    private array $tags = [
        'a' => true,
        'i' => true,
        'b' => true,
        'br' => true,
        'em' => true,
        'strong' => true,
        'sub' => true,
        'sup' => true,
    ];

    /**
     * @param string[] $dictionaries
     * @return string|null
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        // <b>, </b>
        if (isset($this->tags[$word->word])) {
            $after = $string[$word->position + 1];
            if ($after !== '>' && $after !== ' ') {
                return self::RESULT_HTML;
            }

            $before = $string[$word->position - 1];
            if (($before === '<' || ($before === '/' && $string[$word->position - 2] === '<'))) {
                return self::RESULT_HTML;
            }
        }

        return null;
    }

}
