<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\RowHelper;
use SpellChecker\Word;
use function implode;
use function preg_match;
use function sprintf;
use function strpos;
use function trim;

/**
 * Searches for signs, that the word is a regular expression modifier
 */
class RegularExpressionDetector implements Heuristic
{

    public const string RESULT_RE = 're';

    private string $modifiers = 'imsxADSUXJu';

    /** @var string[] */
    private array $prefixes = [
        '[^\\w]preg[^\\w]',
        '[^\\w]ereg[^\\w]',
        '[^\\w]match[^\\w]',
    ];

    private string $pattern;

    /**
     * @param string[] $dictionaries
     * @return string|null
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        $this->pattern ??= sprintf('/(?:%s)(.*)$/', implode('|', $this->prefixes));
        if ($word->block !== null) {
            return null;
        }
        if (trim($word->word, $this->modifiers) !== '') {
            return null;
        }

        $word->row ??= RowHelper::getRowAtPosition($string, $word->position);

        if (preg_match($this->pattern, $word->row, $match)) {
            if (strpos($match[1], $word->word) !== false) {
                return self::RESULT_RE;
            }
        }

        return null;
    }

}
