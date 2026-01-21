<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

use SpellChecker\Word;
use function define;
use function defined;
use function explode;
use function is_string;
use function ltrim;
use function preg_match_all;
use function rtrim;
use function str_replace;
use function strlen;
use function substr;
use function token_get_all;
use function trim;
use const PHP_INT_MAX;
use const PREG_OFFSET_CAPTURE;
use const T_COMMENT;
use const T_CONSTANT_ENCAPSED_STRING;
use const T_DOC_COMMENT;
use const T_ENCAPSED_AND_WHITESPACE;
use const T_HALT_COMPILER;
use const T_INLINE_HTML;
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NAME_RELATIVE;
use const T_START_HEREDOC;
use const T_STRING;
use const T_STRING_VARNAME;
use const T_VARIABLE;

class PhpParser implements Parser
{

    public const string CONTEXT_CODE = 'code';
    public const string CONTEXT_STRING = 'string';
    public const string CONTEXT_COMMENT = 'comment';
    public const string CONTEXT_DOC = 'doc';
    public const string CONTEXT_HTML = 'html';
    public const string CONTEXT_DATA = 'data';

    private DefaultParser $defaultParser;

    public function __construct(DefaultParser $defaultParser)
    {
        $this->defaultParser = $defaultParser;

        // PHP 8 compatibility
        if (!defined('T_NAME_FULLY_QUALIFIED')) {
            define('T_NAME_FULLY_QUALIFIED', PHP_INT_MAX);
        }
        if (!defined('T_NAME_QUALIFIED')) {
            define('T_NAME_QUALIFIED', PHP_INT_MAX);
        }
        if (!defined('T_NAME_RELATIVE')) {
            define('T_NAME_RELATIVE', PHP_INT_MAX);
        }
    }

    /**
     * @return Word[]
     */
    public function parse(string $string): array
    {
        $results = [];
        $lastHeredoc = null;

        $lastRowNumber = 0;
        $nextPosition = 0;
        foreach (token_get_all($string) as $token) {
            $position = $nextPosition;
            if (is_string($token)) {
                $value = $token;
                $rowNumber = $lastRowNumber;
            } else {
                [$token, $value, $rowNumber] = $token;
            }
            $nextPosition += strlen((string) $value);

            switch ($token) {
                case T_COMMENT:
                    // // or #, and /* */
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_COMMENT);
                    break;
                case T_DOC_COMMENT:
                    // /** */
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_DOC);
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                    // "foo" or 'bar'
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_STRING);
                    break;
                case T_START_HEREDOC:
                    // <<<FOO
                    $lastHeredoc = trim(substr($value, 3), "'\"\n");
                    $position += strlen(rtrim($value, "'\"\n")) - strlen($lastHeredoc);
                    $this->defaultParser->blocksToWords($lastHeredoc, $position, $rowNumber, $results, self::CONTEXT_CODE);
                    break;
                case T_ENCAPSED_AND_WHITESPACE:
                    // parts of heredoc or string with variables
                    if ($lastHeredoc !== null && substr($value, -strlen($lastHeredoc) - 1) === $lastHeredoc . ';') {
                        $value = substr($value, 0, -strlen($lastHeredoc) - 1);
                        $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_STRING);
                        $offset = strlen($value);
                        $rows = strlen($value) - strlen(str_replace("\n", '', $value));
                        $this->defaultParser->blocksToWords($lastHeredoc, $position + $offset, $rowNumber + $rows, $results, self::CONTEXT_CODE);
                        $lastHeredoc = null;
                    } else {
                        $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_STRING);
                    }
                    break;
                case T_STRING:
                    // identifiers, e.g. keywords like parent and self, function names, class names and more
                    $this->defaultParser->blocksToWords($value, $position, $rowNumber, $results, self::CONTEXT_CODE);
                    break;
                case T_NAME_FULLY_QUALIFIED:
                case T_NAME_QUALIFIED:
                case T_NAME_RELATIVE:
                    // PHP 8 name tokens
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_CODE);
                    break;
                case T_VARIABLE:
                    // $foo
                    $nameValue = ltrim($value, '$');
                    $position += strlen($value) - strlen($nameValue);
                    $this->defaultParser->blocksToWords($nameValue, $position, $rowNumber, $results, self::CONTEXT_CODE);
                    break;
                case T_STRING_VARNAME:
                    $this->defaultParser->blocksToWords($value, $position, $rowNumber, $results, self::CONTEXT_CODE);
                    break;
                case T_INLINE_HTML:
                    // any text outside <?php
                    $this->parseString($results, $value, $position, $rowNumber, self::CONTEXT_HTML);
                    break;
                case T_HALT_COMPILER:
                    // anything after __halt_compiler(); instructions
                    $after = substr($string, $position + 18);
                    $this->parseString($results, $after, $position + 18, $rowNumber, self::CONTEXT_DATA);
                    break 2;
            }
        }

        return $results;
    }

    /**
     * @param Word[] $result
     */
    private function parseString(array &$result, string $string, int $filePosition, int $rowNumber, string $context): void
    {
        $rowOffset = 0;
        foreach (explode("\n", $string) as $rowIndex => $row) {
            if (!preg_match_all(DefaultParser::WORD_BLOCK_REGEXP, $row, $blockMatches, PREG_OFFSET_CAPTURE)) {
                $rowOffset += strlen($row) + 1;
                continue;
            }
            foreach ($blockMatches[0] as [$block, $rowPosition]) {
                $this->defaultParser->blocksToWords(
                    $block,
                    $filePosition + $rowOffset + $rowPosition,
                    $rowNumber + $rowIndex,
                    $result,
                    $context
                );
            }
            $rowOffset += strlen($row) + 1;
        }
    }

}
