<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\RowHelper;
use SpellChecker\Word;
use function implode;
use function preg_match_all;
use function sprintf;
use function strrpos;

/**
 * Finds out if a word is probably part of file path and tries to match it against dictionaries without accents
 */
class FileNameDetector implements Heuristic
{

    public const string RESULT_FILE_NAME = 'file';

    private DictionaryCollection $dictionaries;

    /** @var string[] */
    private array $fileExtensions = ['html', 'xml', 'js', 'styl', 'css', 'php', 'latte', 'csv', 'pdf', 'jpg', 'png', 'docx', 'svg'];

    private string $pattern;

    public function __construct(DictionaryCollection $dictionaries)
    {
        $this->dictionaries = $dictionaries;
        $this->pattern = sprintf('~[A-Za-z0-9_/%%-]+\\.(?:%s)~', implode('|', $this->fileExtensions));
    }

    /**
     * @param string[] $dictionaries
     * @return string|string
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        $word->row ??= RowHelper::getRowAtPosition($string, $word->position);

        if (preg_match_all($this->pattern, $word->row, $matches)) {
            foreach ($matches[0] as $match) {
                if (strrpos($match, $word->word) !== false) {
                    if ($this->dictionaries->contains($dictionaries, $word->word, $word->context, DictionarySearch::TRY_CAPITALIZED | DictionarySearch::TRY_WITHOUT_DIACRITICS)) {
                        return self::RESULT_FILE_NAME;
                    }
                }
            }
        }

        return null;
    }

}
