<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\RowHelper;
use SpellChecker\Word;
use function implode;
use function preg_match;
use function sprintf;
use function strpos;

/**
 * Searches for signs, that the word is a table name abbreviation used in SQL code
 */
class SqlTableShortcutDetector implements Heuristic
{

    public const RESULT_SQL = 'sql';

    /** @var string[] */
    private $prefixes = [
        // SQL
        '[^\\w]SELECT\\s',
        '[^\\w]FROM\\s',
        '[^\\w]JOIN\\s',
        '[^\\w]WHERE\\s',
        '[^\\w]GROUP BY\\s',
        '[^\\w]ORDER BY\\s',
        '[^\\w]AND\\s',
        '[^\\w]OR\\s',
        '[^\\w]COUNT[\\s(]',
        '[^\\w]SUM[\\s(]',
        '[^\\w]MIN[\\s(]',
        '[^\\w]MAX[\\s(]',
        '[^\\w]GROUP_CONCAT[\\s(]',
        '[^\\w]COALESCE[\\s(]',
        '[^\\w]IF[\\s(]',
        '[^\\w]IFNULL[\\s(]',
        '[^\\w]LEAST[\\s(]',
        // Doctrine etc.
        '->select\\(',
        '->addSelect\\(',
        '->distinct\\(',
        '->update\\(',
        '->delete\\(',
        '->indexBy\\(',
        '->set\\(',
        '->from\\(',
        '->join\\(',
        '->leftJoin\\(',
        '->innerJoin\\(',
        '->on\\(',
        '->where\\(',
        '->andWhere\\(',
        '->orWhere\\(',
        '->groupBy\\(',
        '->addGroupBy\\(',
        '->having\\(',
        '->andHaving\\(',
        '->orHaving\\(',
        '->orderBy\\(',
        '->addOrderBy\\(',
        '->addRootEntityFromClassMetadata\\(',
    ];

    /** @var string */
    private $pattern;

    /**
     * @param Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return string|null
     */
    public function check(Word $word, string &$string, array $dictionaries): ?string
    {
        if ($this->pattern === null) {
            $this->pattern = sprintf('/(?:%s)(.*)$/', implode('|', $this->prefixes));
        }
        if ($word->block !== null) {
            return null;
        }
        if (!preg_match('/^[a-z][a-z0-9]{0,5}$/', $word->word)) {
            return null;
        }

        if ($word->row === null) {
            $word->row = RowHelper::getRowAtPosition($string, $word->position);
        }

        if (preg_match($this->pattern, $word->row, $match)) {
            if (strpos($match[1], $word->word) !== false) {
                return self::RESULT_SQL;
            }
        }

        return null;
    }

}
