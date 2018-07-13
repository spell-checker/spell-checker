<?php declare(strict_types = 1);

// phpcs:disable Squiz.Arrays.ArrayDeclaration.ValueNoNewline


namespace SpellChecker\Parser;

use function array_flip;
use function array_keys;
use function array_merge;
use function array_values;
use function count;
use function implode;
use function is_string;
use function ord;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Parser that separates words in "code", "string", "comment" and "doc" contexts for many languages.
 * Does not implement string variables interpolation - variables are returned as "string" context.
 * Context "data" is supported for data sections in PHP, Perl and Ruby.
 *
 * Supported programming languages:
 * - ABAP, ActionScript, Ada, AppleScript, Assembly
 * - Bash, Basic
 * - C, C++, C#, Clojure, Cobol, CoffeeScript
 * - D, Dart
 * - E, Eiffel, Elm, Erlang
 * - F#, Forth, Factor, Fortran
 * - Go
 * - Haskell
 * - Java, JavaScript
 * - Kotlin
 * - Lisp, Lua
 * - Mathematica, MatLab, Modula
 * - Oberon, OCaml
 * - Pascal, Perl, PHP, PostScript, PowerShell, Prolog, Python
 * - R, Racket, Ruby, Rust
 * - Scala, Scheme, Simula, SmallTalk, SQL (standard + MySQL), Swift
 * - Tcl
 * - VisualBasic
 *
 * Supported templating/markup/styling/config languages:
 * - Blade (Laravel)
 * - CSS
 * - Django
 * - EJS
 * - HTML
 * - .ini
 * - JSX
 * - LaTeX, Latte (Nette)
 * - Mustache
 * - Neon
 * - Smarty
 * - TeX, Twig (Symfony)
 * - Volt (Phalcon)
 * - XML
 * - Yaml
 */
class UniversalParser implements \SpellChecker\Parser\Parser
{

    private const NUMBERS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    private const LETTERS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
    ];

    /** @var int[] */
    private static $numbersKey;

    /** @var int[] */
    private static $nameCharsKey;

    /** @var \SpellChecker\Parser\PlainTextParser */
    private $defaultParser;

    /** @var \SpellChecker\Parser\UniversalParserSettings */
    private $settings;

    /**
     * @param \SpellChecker\Parser\PlainTextParser $defaultParser
     * @param \SpellChecker\Parser\UniversalParserSettings|string $settings
     */
    public function __construct(PlainTextParser $defaultParser, $settings)
    {
        if (is_string($settings)) {
            $settings = UniversalParserSettings::get($settings);
        }

        $this->defaultParser = $defaultParser;
        $this->settings = $settings;

        if (self::$numbersKey === null) {
            self::$numbersKey = array_flip(self::NUMBERS);
            self::$nameCharsKey = array_flip(array_merge(self::LETTERS, self::NUMBERS, ['_', '-']));
        }
    }

    public function getSettings(): UniversalParserSettings
    {
        return $this->settings;
    }

    /**
     * @param string $string
     * @return \SpellChecker\Word[]
     */
    public function parse(string $string): array
    {
        $results = [];

        $position = 0;
        $rowNumber = 1;

        // specials
        $luaDocBlockLastRow = null;

        $length = strlen($string);
        while ($position < $length && ($char = $string[$position]) !== false) {
            $position++;

            switch ($char) {
                case "\x00":
                case "\x01":
                case "\x02":
                case "\x03":
                case "\x04":
                case "\x05":
                case "\x06":
                case "\x07":
                case "\x08":
                case "\t":
                    break;
                case "\n":
                    $rowNumber++;
                    break;
                case "\x0B":
                case "\f":
                case "\r":
                case "\x0E":
                case "\x0F":
                case "\x10":
                case "\x11":
                case "\x12":
                case "\x13":
                case "\x14":
                case "\x15":
                case "\x16":
                case "\x17":
                case "\x18":
                case "\x19":
                case "\x1A":
                case "\e":
                case "\x1C":
                case "\x1D":
                case "\x1E":
                case "\x1F":
                case "\x7F":
                    break;
                case '\'':
                    if ($this->settings->tripleApostropheString !== null && $string[$position] === "'" && $string[$position + 1] === "'") {
                        // ''' foo '''
                        $this->parseStringUntil($results, $string, $position, $rowNumber, "'''", $this->settings->tripleApostropheString);
                    } elseif ($this->settings->apostropheComment !== null) {
                        // ' foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->apostropheComment);
                    } elseif ($this->settings->apostropheString !== null) {
                        // 'foo'
                        $this->parseString($results, $string, $position, $rowNumber, "'", ...$this->settings->apostropheStringsSettings());
                    }
                    break;
                case '*':
                    if ($this->settings->asteriskComment !== null) {
                        // * foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->asteriskComment);
                    } elseif ($this->settings->asteriskArrowComment !== null) {
                        // *> foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->asteriskArrowComment);
                    }
                    break;
                case '\\':
                    if ($this->settings->backslashComment !== null) {
                        // \ foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->backslashComment);
                    }
                    break;
                case '`':
                    if ($this->settings->backtickString !== null) {
                        // `foo`
                        $this->parseString($results, $string, $position, $rowNumber, '`', ...$this->settings->backtickStringsSettings());
                    }
                    break;
                case '!':
                    if ($this->settings->bangComment !== null) {
                        // ! foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->bangComment);
                    }
                    break;
                case '#':
                    if ($this->settings->tripleHashBlockComment !== null && $string[$position] === '#' && $string[$position + 1] === '#') {
                        // ### foo ###
                        $this->parseUntil($results, $string, $position, $rowNumber, '###', $this->settings->tripleHashBlockComment);
                    } elseif ($this->settings->racketHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === '<') {
                        // #<<EOF foo EOF
                        $position += 2;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber);
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->racketHeredoc);
                        }
                    } elseif ($this->settings->hashPipeBlock !== null && $string[$position] === '|') {
                        // #| foo |#
                        $this->parseUntil($results, $string, $position, $rowNumber, '|#', $this->settings->hashPipeBlock);
                    } elseif ($this->settings->hashComment !== null) {
                        // # foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->hashComment);
                    }
                    break;
                case '-':
                    if ($this->settings->luaBlockComment !== null && $string[$position] === '-' && $string[$position + 1] === '[' && $string[$position + 2] === '[') {
                        // --[[ foo ]]
                        $this->parseUntil($results, $string, $position, $rowNumber, ']]', $this->settings->luaBlockComment);
                    } elseif ($this->settings->luaDocComment !== null && $string[$position] === '-'
                        && ($string[$position + 1] === '-' || $luaDocBlockLastRow === $rowNumber - 1)) {
                        // --- foo
                        // -- bar
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->luaDocComment);
                        $luaDocBlockLastRow = $rowNumber;
                    } elseif ($this->settings->doubleDashComment !== null && $string[$position] === '-') {
                        // -- foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->doubleDashComment);
                    }
                    break;
                case '%':
                    if ($this->settings->percentCurlyBlock !== null && $string[$position] === '{') {
                        // %{ foo %}
                        $this->parseUntil($results, $string, $position, $rowNumber, '%}', $this->settings->percentCurlyBlock);
                    } elseif ($this->settings->percentComment !== null) {
                        // % foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->percentComment);
                    }
                    break;
                case '"':
                    if ($this->settings->tripleQuoteString !== null && $string[$position] === '"' && $string[$position + 1] === '"') {
                        // """foo"""
                        $this->parseStringUntil($results, $string, $position, $rowNumber, '"""', $this->settings->tripleQuoteString);
                    } elseif ($this->settings->quoteString !== null) {
                        // "foo"
                        $this->parseString($results, $string, $position, $rowNumber, '"', ...$this->settings->quoteStringsSettings());
                    } elseif ($this->settings->quoteSlashComment !== null) {
                        // "/ foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->quoteSlashComment);
                    } elseif ($this->settings->quoteComment !== null) {
                        // " foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->quoteComment);
                    }
                    break;
                case ';':
                    if ($this->settings->semicolonComment !== null) {
                        // ; foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->semicolonComment);
                    }
                    break;
                case '/':
                    if ($this->settings->tripleSlashComment !== null && $string[$position] === '/' && $string[$position + 1] === '/') {
                        // /// foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->tripleSlashComment);
                    } elseif ($this->settings->doubleSlashBangComment !== null && $string[$position] === '/' && $string[$position + 1] === '!') {
                        // //! foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->doubleSlashBangComment);
                    } elseif ($this->settings->doubleSlashComment !== null && $string[$position] === '/') {
                        // // foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->doubleSlashComment);
                    } elseif ($this->settings->slashDoubleStarBlock !== null && $string[$position] === '*' && $string[$position + 1] === '*') {
                        // /** foo */
                        $this->parseUntil($results, $string, $position, $rowNumber, '*/', $this->settings->slashDoubleStarBlock);
                    } elseif ($this->settings->slashStarBlock !== null && $string[$position] === '*') {
                        // /* foo */
                        $this->parseUntil($results, $string, $position, $rowNumber, '*/', $this->settings->slashStarBlock);
                    } elseif ($this->settings->slashPlusBlock !== null && $string[$position] === '+') {
                        // /+ foo +/
                        $this->parseUntil($results, $string, $position, $rowNumber, '+/', $this->settings->slashPlusBlock);
                    } elseif ($this->settings->slashComment !== null) {
                        // / foo
                        $this->parseUntil($results, $string, $position, $rowNumber, "\n", $this->settings->slashComment);
                    }
                    break;
                case '(':
                    if ($this->settings->parenDoubleStarBlock !== null && $string[$position] === '*' && $string[$position + 1] === '*') {
                        // (** foo *)
                        $this->parseUntil($results, $string, $position, $rowNumber, '*)', $this->settings->parenDoubleStarBlock);
                    } elseif ($this->settings->parenStarBlock !== null && $string[$position] === '*') {
                        // (* foo *)
                        $this->parseUntil($results, $string, $position, $rowNumber, '*)', $this->settings->parenStarBlock);
                    } elseif ($this->settings->parenthesesString !== null) {
                        // ( foo )
                        $this->parseString($results, $string, $position, $rowNumber, ')', $this->settings->parenthesesString, true, '');
                    }
                    break;
                case '[':
                    if ($this->settings->doubleSquareString !== null && $string[$position] === '[') {
                        // [[ foo ]]
                        $this->parseStringUntil($results, $string, $position, $rowNumber, ']]', $this->settings->doubleSquareString);
                    }
                    break;
                case '{':
                    if ($this->settings->curlyStarBlock !== null && $string[$position] === '*') {
                        // {* foo *}
                        $this->parseUntil($results, $string, $position, $rowNumber, '*}', $this->settings->curlyStarBlock);
                    } elseif ($this->settings->curlyDashBlock !== null && $string[$position] === '-') {
                        // {- foo -}
                        $this->parseUntil($results, $string, $position, $rowNumber, '-}', $this->settings->curlyDashBlock);
                    } elseif ($this->settings->curlyHashBlock !== null && $string[$position] === '#') {
                        // {# foo #}
                        $this->parseUntil($results, $string, $position, $rowNumber, '#}', $this->settings->curlyHashBlock);
                    } elseif ($this->settings->curlyBlock !== null) {
                        // { foo }
                        $this->parseUntil($results, $string, $position, $rowNumber, '}', $this->settings->curlyBlock);
                    }
                    break;
                case '<':
                    if ($this->settings->arrowHashBlock !== null && $string[$position] === '#') {
                        // <# foo #>
                        $this->parseUntil($results, $string, $position, $rowNumber, '#>', $this->settings->arrowHashBlock);
                    } elseif ($this->settings->xmlBlockComment !== null && $string[$position] === '!' && $string[$position + 1] === '-' && $string[$position + 2] === '-') {
                        // <!-- foo -->
                        $this->parseUntil($results, $string, $position, $rowNumber, '-->', $this->settings->xmlBlockComment);
                    } elseif ($this->settings->hereString !== null && $string[$position] === '<' && $string[$position + 1] === '<') {
                        // <<< foo
                        $this->parseStringUntil($results, $string, $position, $rowNumber, "\n", $this->settings->hereString);
                    } elseif ($this->settings->phpApostropheHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === '<' && $string[$position + 2] === "'") {
                        // <<<'EOF' foo EOF
                        $position += 3;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber, "'");
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->phpHeredoc);
                        }
                    } elseif ($this->settings->phpQuoteHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === '<' && $string[$position + 2] === '"') {
                        // <<<"EOF" foo EOF
                        $position += 3;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber, '"');
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->phpHeredoc);
                        }
                    } elseif ($this->settings->phpHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === '<') {
                        // <<<EOF foo EOF
                        $position += 2;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber);
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->phpHeredoc);
                        }
                    } elseif ($this->settings->indentHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === '-') {
                        // <<-EOF foo EOF
                        $position += 2;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber);
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->indentHeredoc);
                        }
                    } elseif ($this->settings->indentHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === '~') {
                        // <<~EOF foo EOF
                        $position += 2;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber);
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->rubyIndentHeredoc);
                        }
                    } elseif ($this->settings->apostropheHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === "'") {
                        // <<'EOF' foo EOF
                        $position += 2;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber, "'");
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->apostropheHeredoc);
                        }
                    } elseif ($this->settings->quoteHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === '"') {
                        // <<"EOF" foo EOF
                        $position += 2;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber, '"');
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->quoteHeredoc);
                        }
                    } elseif ($this->settings->backtickHeredoc !== null && $string[$position] === '<' && $string[$position + 1] === '`') {
                        // <<`EOF` foo EOF
                        $position += 2;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber, '`');
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->backtickHeredoc);
                        }
                    } elseif ($this->settings->heredoc !== null && $string[$position] === '<') {
                        // <<EOF  foo EOF
                        $position++;
                        $delimiter = $this->findHeredocDelimiter($results, $string, $position, $rowNumber);
                        if ($delimiter !== null) {
                            $this->parseStringUntil($results, $string, $position, $rowNumber, $delimiter, $this->settings->heredoc);
                        }
                    } elseif ($this->settings->xmlTagName !== null) {
                        ///
                        count([]);
                    }
                    break;
                case '=':
                    if ($this->settings->rubyBlockComment !== null && substr($string, $position, 5) === 'begin') {
                        // =begin foo =cut
                        $this->parseUntil($results, $string, $position, $rowNumber, '=end', $this->settings->rubyBlockComment);
                    } elseif ($this->settings->perlBlockComment !== null && $string[$position - 2] === "\n") {
                        // =pod foo =cut
                        $this->parseUntil($results, $string, $position, $rowNumber, '=cut', $this->settings->perlBlockComment);
                    }
                    break;
                case '@':
                    if ($this->settings->atApostropheBlock !== null && $string[$position] === "'") {
                        // @' foo '@
                        $this->parseStringUntil($results, $string, $position, $rowNumber, "'@", $this->settings->atApostropheBlock);
                    } elseif ($this->settings->atQuoteBlock !== null && $string[$position] === '"') {
                        // @" foo "@
                        $this->parseStringUntil($results, $string, $position, $rowNumber, '"@', $this->settings->atQuoteBlock);
                    }
                    break;
                case ':':
                case '$':
                case '&':
                case '+':
                case '^':
                case '|':
                case '~':
                case '.':
                case ',':
                case '?':
                case ')':
                case ']':
                case '}':
                case '>':
                case ' ':
                    // ignore
                    break;
                case '_':
                    if ($this->settings->dataSegment !== null && substr($string, $position - 1, 8) === '__DATA__') {
                        $position += 7;
                        $data = substr($string, $position, strlen($string) - $position);
                        $this->defaultParser->parseText($results, $data, $position, $rowNumber, $this->settings->dataSegment);
                        break 2;
                    } elseif ($this->settings->endDataSegment !== null && substr($string, $position - 1, 7) === '__END__') {
                        $position += 8;
                        $data = substr($string, $position, strlen($string) - $position);
                        $this->defaultParser->parseText($results, $data, $position, $rowNumber, $this->settings->endDataSegment);
                        break 2;
                    } elseif ($this->settings->phpDataSegment !== null && substr($string, $position - 1, 17) === '__halt_compiler()') {
                        $position += 17;
                        $data = substr($string, $position, strlen($string) - $position);
                        $this->defaultParser->parseText($results, $data, $position, $rowNumber, $this->settings->phpDataSegment);
                        break 2;
                    }
                    // fall through
                default:
                    // letters, numbers, "_", and codes over 127
                    $value = $char;
                    $startPosition = $position - 1;
                    while (($next = substr($string, $position, 1)) !== false) {
                        if (isset(self::$nameCharsKey[$next]) || ord($next) > 127) {
                            $value .= $next;
                            $position++;
                        } else {
                            break;
                        }
                    }
                    if (isset($keywordsKey[$value])) {
                        break;
                    }
                    $this->defaultParser->blocksToWords($value, $startPosition, $rowNumber, $results, Context::CODE);
                    break;
            }
        }

        return $results;
    }

    /**
     * @param \SpellChecker\Word[] $results
     * @param string $string
     * @param int $position
     * @param int $rowNumber
     * @param string $quote
     * @return string|null
     */
    private function findHeredocDelimiter(array &$results, string &$string, int &$position, int &$rowNumber, string $quote = ''): ?string
    {
        $rowEndPosition = strpos($string, "\n", $position);
        if ($rowEndPosition === false) {
            return null;
        }
        $delimiter = trim(substr($string, $position, $rowEndPosition - $position));

        if ($quote !== '') {
            $delimiter = trim($delimiter, $quote);
        }
        $this->defaultParser->parseText($results, $delimiter, $position, $rowNumber, $this->settings->defaultContext);

        $position = $rowEndPosition + 1;
        $rowNumber++;

        return $delimiter;
    }

    /**
     * @param \SpellChecker\Word[] $results
     * @param string $string
     * @param int $position
     * @param int $rowNumber
     * @param string $end
     * @param string $context
     */
    private function parseUntil(array &$results, string &$string, int &$position, int &$rowNumber, string $end, string $context): void
    {
        $endPosition = strpos($string, $end, $position);
        if ($endPosition === false) {
            $endPosition = strlen($string);
        }
        $rawValue = substr($string, $position, $endPosition - $position);

        if ($rawValue !== '') {
            $this->defaultParser->parseText($results, $rawValue, $position, $rowNumber, $context);
        }
        $position += strlen($rawValue);
        $rowNumber += strlen($rawValue) - strlen(str_replace("\n", '', $rawValue));
    }

    /**
     * @param \SpellChecker\Word[] $results
     * @param string $string
     * @param int $position
     * @param int $rowNumber
     * @param string $end
     * @param string $context
     */
    private function parseStringUntil(array &$results, string &$string, int &$position, int &$rowNumber, string $end, string $context): void
    {
        $endPosition = strpos($string, $end, $position);
        if ($endPosition === false) {
            $endPosition = strlen($string);
        }
        $rawValue = substr($string, $position, $endPosition - $position - 1);

        if ($rawValue !== '') {
            $value = $this->unescapeString($rawValue, '', '', false);
            $this->defaultParser->parseText($results, $value, $position, $rowNumber, $context);
        }
        $position += strlen($rawValue);
        $rowNumber += strlen($rawValue) - strlen(str_replace("\n", '', $rawValue));
    }

    /**
     * @param \SpellChecker\Word[] $results
     * @param string $string
     * @param int $position
     * @param int $rowNumber
     * @param string $quote
     * @param string $context
     * @param bool $backslashes
     * @param string $escape
     */
    private function parseString(
        array &$results,
        string &$string,
        int &$position,
        int &$rowNumber,
        string $quote,
        string $context,
        bool $backslashes,
        string $escape
    ): void
    {
        $length = strlen($string);
        $startPosition = $position;
        $startRow = $rowNumber;

        $orig = [];
        $escaped = false;
        while ($next = $string[$position]) {
            if ($next === false) {
                throw new \SpellChecker\Parser\UniversalParser\EndOfStringNotFoundException();
            } elseif ($next === $quote) {
                $orig[] = $next;
                $position++;
                if ($escaped) {
                    $escaped = false;
                } elseif (strlen($string) > $position && $string[$position] === $escape) {
                    $escaped = true;
                } else {
                    break;
                }
            } elseif ($next === "\n") {
                $orig[] = $next;
                $position++;
                $rowNumber++;
            } elseif ($backslashes && $next === '\\') {
                $escaped = !$escaped;
                $orig[] = $next;
                $position++;
            } else {
                $orig[] = $next;
                $position++;
            }
            if ($position >= $length) {
                break;
            }
        }

        if ($context === Context::SKIP) {
            return;
        }

        $orig = implode('', $orig);
        $value = $this->unescapeString($orig, $quote, $escape, $backslashes);

        $this->defaultParser->parseText($results, $value, $startPosition, $startRow, $context);
    }

    /**
     * \0    An ASCII NUL (X'00') character
     * \'    A single quote (') character
     * \"    A double quote (") character
     * \b    A backspace character
     * \n    A newline (linefeed) character
     * \r    A carriage return character
     * \t    A tab character
     * \Z    ASCII 26 (Control+Z)
     * \\    A backslash (\) character
     *
     * @param string $string
     * @param string $quote
     * @param string $escape
     * @param bool $backslashes
     * @return string
     */
    private function unescapeString(string $string, string $quote, string $escape, bool $backslashes): string
    {
        static $translations = [
            '\\0' => "\x00",
            '\\\'' => '\'',
            '\\""' => '""',
            '\\b' => "\x08",
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\Z' => "\x1A",
        ];
        $string = str_replace(array_keys($translations), array_values($translations), $string);

        if ($backslashes) {
            $string = str_replace(['\\\\', '\\' . $quote], ['\\', $quote], $string);
        }
        if ($escape) {
            $string = str_replace($escape . $quote, $quote, $string);
        }

        return $string;
    }

}
