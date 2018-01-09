<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\Word;

/**
 * Finds out if a word is probably part of file path and tries to match it against dictionaries without accents
 */
class FileNameDetector implements \SpellChecker\Heuristic\Heuristic
{

    /** @var \SpellChecker\Dictionary\DictionaryCollection */
    private $dictionaries;

    /** @var string[] */
    private $fileExtensions = [
        'html', 'xml', 'js', 'styl', 'css', 'php', 'latte', 'csv', 'pdf', 'jpg', 'png', 'docx',
    ];

    /** @var string */
    private $pattern;

    public function __construct(DictionaryCollection $dictionaries)
    {
        $this->dictionaries = $dictionaries;
        $this->pattern = sprintf('~[A-Za-z0-9_/%%-]+\\.(?:%s)~', implode('|', $this->fileExtensions));
    }

    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        $row = substr($string, $word->rowStart, $word->rowEnd - $word->rowStart);

        if (preg_match_all($this->pattern, $row, $matches)) {
            foreach ($matches[0] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->contains($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED)) {
                        return true;
                    }
                    if ($this->dictionaries->containsWithoutDiacritics($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

}
