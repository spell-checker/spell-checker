<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\RowHelper;
use SpellChecker\Word;

/**
 * Searches for signs, that the word is a table name abbreviation used in SQL code
 */
class SqlTableShortcutDetector implements \SpellChecker\Heuristic\Heuristic
{

    /** @var string[] */
    private $prefixes = [
        // SQL
        'SELECT\\s',
        'FROM\\s',
        'JOIN\\s',
        'WHERE\\s',
        'GROUP BY\\s',
        'ORDER BY\\s',
        'AND\\s',
        'OR\\s',
        'COUNT[\\s(]',
        'SUM[\\s(]',
        'MIN[\\s(]',
        'MAX[\\s(]',
        'GROUP_CONCAT[\\s(]',
        'COALESCE[\\s(]',
        'IF[\\s(]',
        'IFNULL[\\s(]',
        'LEAST[\\s(]',
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
     * @param \SpellChecker\Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return bool
     */
    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        if ($this->pattern === null) {
            $this->pattern = sprintf('/(?:%s)(.*)$/', implode('|', $this->prefixes));
        }
        if ($word->block !== null) {
            return false;
        }
        if (!preg_match('/^[a-z][a-z0-9]{0,5}$/', $word->word)) {
            return false;
        }

        if ($word->row === null) {
            $word->row = RowHelper::getRowAtPosition($string, $word->position);
        }

        if (preg_match($this->pattern, $word->row, $match)) {
            if (strpos($match[1], $word->word) !== false) {
                return true;
            }
        }

        return false;
    }

}
