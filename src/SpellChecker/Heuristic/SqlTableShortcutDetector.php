<?php declare(strict_types = 1);

namespace SpellChecker\Heuristic;

use SpellChecker\Word;

class SqlTableShortcutDetector implements \SpellChecker\Heuristic\Heuristic
{

    /** @var string[] */
    private $prefixes = [
        // SQL
        'SELECT',
        'FROM',
        'JOIN',
        'WHERE',
        'GROUP BY',
        'ORDER BY',
        'AND',
        'OR',
        'COUNT',
        'SUM',
        'MIN',
        'MAX',
        'GROUP_CONCAT',
        'COALESCE',
        'IF',
        'IFNULL',
        'LEAST',
        // Doctrine etc.
        '->select(',
        '->addSelect(',
        '->delete(',
        '->from(',
        '->join(',
        '->leftJoin(',
        '->on(',
        '->where(',
        '->andWhere(',
        '->orWhere(',
        '->groupBy(',
        '->addGroupBy(',
        '->orderBy(',
        '->addOrderBy(',
        '->addRootEntityFromClassMetadata(',
        '->applyMailStatisticsOrderSubqueryForFilter(',
    ];

    /** @var string */
    private $pattern;

    /**
     * Searches for signs, that the word is a table shortcut used in SQL FROM, JOIN
     * or previously used table shortcut used in WHERE, SELECT, HAVING, ON
     * @param \SpellChecker\Word $word
     * @param string $string
     * @param string[] $dictionaries
     * @return bool
     */
    public function check(Word $word, string &$string, array $dictionaries): bool
    {
        if ($this->pattern === null) {
            $this->pattern = sprintf('/(?:%s)(.*)$/', implode('|', array_map('preg_quote', $this->prefixes)));
        }
        if ($word->block !== null) {
            return false;
        }
        if (!preg_match('/^[a-z][a-z0-9]{0,5}$/', $word->word)) {
            return false;
        }

        $row = substr($string, $word->rowStart, $word->rowEnd - $word->rowStart);
        if (preg_match($this->pattern, $row, $match)) {
            if (strpos($match[1], $word->word) !== false) {
                return true;
            }
        }

        return false;
    }

}
