<?php declare(strict_types = 1);

// phpcs:disable Squiz.WhiteSpace.MemberVarSpacing.Incorrect
// spell-check-ignore: paren attr

namespace SpellChecker\Parser;

use function call_user_func;
use function method_exists;
use function sprintf;

/**
 * Activate features by assigning a Context to them
 */
class UniversalParserSettings
{

    /**
     * Set to Context::STRING for templating languages
     * @var string
     */
    public $defaultContext = Context::CODE;

    // line comments ---------------------------------------------------------------------------------------------------

    /**
     * ' Basic, VisualBasic
     * @var string
     */
    public $apostropheComment;

    /**
     * * ABAP, Cobol
     * @var string
     */
    public $asteriskComment;

    /**
     * *> Cobol
     * @var string
     */
    public $asteriskArrowComment;

    /**
     * \ Forth
     * @var string
     */
    public $backslashComment;

    /**
     * ! Factor, Fortran, Simula
     * @var string
     */
    public $bangComment;

    /**
     * # AppleScript, CoffeeScript, Bash, E, Neon, Perl, PHP, PowerShell, Python, R, Tcl, Yaml
     * @var string
     */
    public $hashComment;

    /**
     * ### CoffeeScript ###
     * @var string
     */
    public $tripleHashBlockComment;

    /**
     * -- Ada, AppleScript, Eiffel, Elm, Lua, Haskell, SQL
     * @var string
     */
    public $doubleDashComment;

    /**
     * --- Lua
     * -- foo
     * @var string
     */
    public $luaDocComment;

    /**
     * % Erlang, LaTeX, MatLab, PostScript, Prolog
     * @var string
     */
    public $percentComment;

    /**
     * " ABAP
     * @var string
     */
    public $quoteComment;

    /**
     * "/ SmallTalk
     * @var string
     */
    public $quoteSlashComment;

    /**
     * ; Assembly, Clojure, .ini, Lisp, Racket, Scheme
     * @var string
     */
    public $semicolonComment;

    /**
     * / Cobol
     * @var string
     */
    public $slashComment;

    /**
     * // ActionScript, C, C++, C#, CSS, D, Dart, F#, Go, Java, JavaScript, Kotlin, PHP, Rust, Scala, Swift
     * @var string
     */
    public $doubleSlashComment;

    /**
     * /// Rust, doc in Dart
     * @var string
     */
    public $tripleSlashComment;

    /**
     * //! Rust
     * @var string
     */
    public $doubleSlashBangComment;

    // block comments --------------------------------------------------------------------------------------------------

    /**
     * /* ActionScript, C, C++, C#, CSS, Dart, D, Java, JavaScript, PHP, Prolog, Kotlin, Scala, Swift * /
     * @var string
     */
    public $slashStarBlock;

    /**
     * /** Java, Kotlin, PHP, Rust * /
     * @var string
     */
    public $slashDoubleStarBlock;

    /**
     * /+ D +/
     * @var string
     */
    public $slashPlusBlock;

    /**
     * {- Elm, Haskell -}
     * @var string
     */
    public $curlyDashBlock;

    /**
     * {* Latte, Smarty *}
     * @var string
     */
    public $curlyStarBlock;

    /**
     * {# Django, Twig, Volt #}
     * @var string
     */
    public $curlyHashBlock;

    /**
     * {{! Mustache }}
     * @var string
     */
    public $doubleCurlyBangBlock;

    /**
     * {{-- Blade --}}
     * @var string
     */
    public $doubleCurlyDoubleDashBlock;

    /**
     * #| Lisp, Racket, Scheme |#
     * @var string
     */
    public $hashPipeBlock;

    /**
     * %{ MatLab %}
     * @var string
     */
    public $percentCurlyBlock;

    /**
     * (* AppleScript, F#, Mathematica, Modula, Oberon, OCaml, Pascal *)
     * @var string
     */
    public $parenStarBlock;

    /**
     * (** Oberon *)
     * @var string
     */
    public $parenDoubleStarBlock;

    /**
     * <# PowerShell #>
     * @var string
     */
    public $arrowHashBlock;

    /**
     * --[[ Lua ]]
     * @var string
     */
    public $luaBlockComment;

    /**
     * =foo Perl =cut
     * @var string
     */
    public $perlBlockComment;

    /**
     * =begin Ruby =end
     * @var string
     */
    public $rubyBlockComment;

    /**
     * <!-- HTML, XML -->
     * @var string
     */
    public $xmlBlockComment;

    // strings ---------------------------------------------------------------------------------------------------------

    /**
     * 'most languages'
     * @var string
     */
    public $apostropheString;
    /** @var bool */
    public $apostropheStringBackslash = true;
    /** @var string */
    public $apostropheStringEscapeChar = '';

    /**
     * "most languages", comment in SmallTalk
     * @var string
     */
    public $quoteString;
    /** @var bool */
    public $quoteStringBackslash = true;
    /** @var string */
    public $quoteStringEscapeChar = '';

    /**
     * `MySQL` (names)
     * @var string
     */
    public $backtickString;
    /** @var bool */
    public $backtickStringBackslash = true;
    /** @var string */
    public $backtickStringEscapeChar = '';

    /**
     * '''Python, CoffeeScript'''
     * @var string
     */
    public $tripleApostropheString;

    /**
     * """Python, CoffeeScript"""
     * @var string
     */
    public $tripleQuoteString;

    /**
     * [[Lua]]
     * @var string
     */
    public $doubleSquareString;

    /**
     * ( PosScript )
     * @var string
     */
    public $parenthesesString;

    /**
     * @' PowerShell '@
     * @var string
     */
    public $atApostropheBlock;

    /**
     * @" PowerShell "@
     * @var string
     */
    public $atQuoteBlock;

    /**
     * << EOF ... EOF (Unix, Perl, Ruby)
     * @var string
     */
    public $heredoc;

    /**
     * <<- EOF ... EOF (Unix, Ruby)
     * @var string
     */
    public $indentHeredoc;

    /**
     * <<~ EOF ... EOF (Ruby)
     * @var string
     */
    public $rubyIndentHeredoc;

    /**
     * << 'EOF' ... EOF (Unix, Perl)
     * @var string
     */
    public $apostropheHeredoc;

    /**
     * << "EOF" ... EOF (Perl)
     * @var string
     */
    public $quoteHeredoc;

    /**
     * << `EOF` ... EOF (Perl)
     * @var string
     */
    public $backtickHeredoc;

    /**
     * <<< ... (Bash)
     * @var string
     */
    public $hereString;

    /**
     * <<<EOF ... EOF (PHP)
     * @var string
     */
    public $phpHeredoc;

    /**
     * <<<'EOF' ... EOF (PHP)
     * @var string
     */
    public $phpApostropheHeredoc;

    /**
     * <<<"EOF" ... EOF (PHP)
     * @var string
     */
    public $phpQuoteHeredoc;

    /**
     * #<<EOF ... EOF (Racket)
     * @var string
     */
    public $racketHeredoc;

    // data segments ---------------------------------------------------------------------------------------------------

    /**
     * __DATA__ ... (Perl, Ruby)
     * @var string
     */
    public $dataSegment;

    /**
     * __END__ ... (Perl)
     * @var string
     */
    public $endDataSegment;

    /**
     * __halt_compiler() ... (PHP)
     * @var string
     */
    public $phpDataSegment;

    // templates & markup ----------------------------------------------------------------------------------------------

    /**
     * <?php ... ?/>
     * @var string
     */
    public $phpTags; ///

    /**
     * <% ... %>
     * @var string
     */
    public $aspTags; ///

    /**
     * { LaTeX, Latte, Smarty, Pascal (comment) }
     * @var string
     */
    public $curlyBlock;

    /**
     * {{ Django, Blade, Latte, Mustache, Twig, Volt }}
     * @var string
     */
    public $doubleCurlyBlock;

    /**
     * {% Django, Twig, Volt %}
     * @var string
     */
    public $curlyDollarBlock;

    /**
     * ${{ Mustache }}
     * @var string
     */
    public $dollarDoubleCurlyBlock;

    /**
     * @{{ Blade }}
     * @var string
     */
    public $atDoubleCurlyBlock;

    /**
     * @foo | @bar ( ... ) (Blade)
     * @var string
     */
    public $atTag;

    /**
     * \foo (LaTeX)
     * @var string
     */
    public $backslashTag;

    /**
     * [ LaTeX ]
     * @var string
     */
    public $squareBlock;

    /**
     * <% EJS %>
     * @var string
     */
    public $arrowPercentBlock;

    /**
     * <tag attr='value'>JSX, HTML, XML</tag>
     * @var string
     */
    public $xmlTagName;
    /** @var string */
    public $xmlTagValue;
    /** @var string */
    public $xmlAttrName;
    /** @var string */
    public $xmlAttrValue;

    // helpers ---------------------------------------------------------------------------------------------------------

    /**
     * @return string[]|bool[]
     */
    public function apostropheStringsSettings(): array
    {
        return [$this->apostropheString, $this->apostropheStringBackslash, $this->apostropheStringEscapeChar];
    }

    /**
     * @return string[]|bool[]
     */
    public function quoteStringsSettings(): array
    {
        return [$this->quoteString, $this->quoteStringBackslash, $this->quoteStringEscapeChar];
    }

    /**
     * @return string[]|bool[]
     */
    public function backtickStringsSettings(): array
    {
        return [$this->backtickString, $this->backtickStringBackslash, $this->backtickStringEscapeChar];
    }

    // constructors ----------------------------------------------------------------------------------------------------

    public static function get(string $language): self
    {
        if (!method_exists(self::class, $language)) {
            throw new \LogicException(sprintf('Constructor for language "%s" is not defined.', $language));
        }

        return call_user_func([self::class, $language]);
    }

    public static function abap(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->asteriskComment = Context::COMMENT;
        $self->quoteComment = Context::COMMENT;

        return $self;
    }

    public static function actionScript(): self
    {
        return self::c();
    }

    public static function ada(): self
    {
        $self = new self();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->doubleDashComment = Context::COMMENT;

        return $self;
    }

    public static function appleScript(): self
    {
        $self = new self();
        $self->quoteString = Context::STRING;
        $self->hashComment = Context::COMMENT;
        $self->doubleDashComment = Context::COMMENT;
        $self->parenStarBlock = Context::COMMENT;

        return $self;
    }

    public static function assembly(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->semicolonComment = Context::COMMENT;

        return $self;
    }

    public static function bash(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->hereString = Context::STRING;
        $self->hashComment = Context::COMMENT;

        return $self;
    }

    public static function basic(): self
    {
        $self = new self();
        $self->quoteString = Context::STRING;
        $self->apostropheComment = Context::COMMENT;

        return $self;
    }

    public static function c(): self
    {
        $self = new self();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->doubleSlashComment = Context::COMMENT;
        $self->slashStarBlock = Context::COMMENT;

        return $self;
    }

    public static function cPlusPlus(): self
    {
        return self::c();
    }

    public static function cSharp(): self
    {
        return self::c();
    }

    public static function clojure(): self
    {
        $self = new self();
        $self->quoteString = Context::STRING;
        $self->semicolonComment = Context::COMMENT;

        return $self;
    }

    public static function cobol(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->asteriskArrowComment = Context::COMMENT;
        $self->slashComment = Context::COMMENT;

        return $self;
    }

    public static function coffeeScript(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->tripleApostropheString = Context::STRING;
        $self->tripleQuoteString = Context::STRING;
        $self->hashComment = Context::COMMENT;
        $self->tripleHashBlockComment = Context::COMMENT;

        return $self;
    }

    public static function d(): self
    {
        $self = self::c();
        $self->slashPlusBlock = Context::COMMENT;

        return $self;
    }

    public static function dart(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->doubleSlashComment = Context::COMMENT;
        $self->tripleSlashComment = Context::DOC;
        $self->slashStarBlock = Context::COMMENT;

        return $self;
    }

    public static function forth(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->backslashComment = Context::COMMENT;

        return $self;
    }

    public static function go(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->doubleSlashComment = Context::COMMENT;

        return $self;
    }

    public static function haskell(): self
    {
        $self = new self();
        $self->quoteString = Context::STRING;
        $self->doubleDashComment = Context::COMMENT;
        $self->curlyDashBlock = Context::COMMENT;

        return $self;
    }

    public static function java(): self
    {
        $self = new self();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->doubleSlashComment = Context::COMMENT;
        $self->slashStarBlock = Context::COMMENT;
        $self->slashDoubleStarBlock = Context::DOC;

        return $self;
    }

    public static function javascript(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->doubleSlashComment = Context::COMMENT;
        $self->slashStarBlock = Context::COMMENT;
        $self->slashDoubleStarBlock = Context::DOC;

        return $self;
    }

    public static function javascriptJsx(): self
    {
        $self = self::javascript();
        self::setupXmlTags($self);

        return $self;
    }

    public static function kotlin(): self
    {
        return self::java();
    }

    public static function lisp(): self
    {
        $self = new self();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->semicolonComment = Context::COMMENT;
        $self->hashPipeBlock = Context::COMMENT;

        return $self;
    }

    public static function lua(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->doubleSquareString = Context::STRING;
        $self->doubleDashComment = Context::COMMENT;
        $self->luaBlockComment = Context::COMMENT;
        $self->luaDocComment = Context::DOC;

        return $self;
    }

    public static function mathematica(): self
    {
        $self = new self();
        $self->quoteString = Context::STRING;
        $self->parenStarBlock = Context::COMMENT;

        return $self;
    }

    public static function matlab(): self
    {
        $self = new self();
        $self->quoteString = Context::STRING;
        $self->percentComment = Context::COMMENT;
        $self->percentCurlyBlock = Context::COMMENT;

        return $self;
    }

    public static function modula(): self
    {
        $self = new self();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->parenStarBlock = Context::COMMENT;

        return $self;
    }

    public static function oberon(): self
    {
        $self = new self();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->parenStarBlock = Context::COMMENT;
        $self->parenDoubleStarBlock = Context::DOC;

        return $self;
    }

    public static function ocaml(): self
    {
        $self = new self();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->parenStarBlock = Context::COMMENT;

        return $self;
    }

    public static function pascal(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->apostropheStringBackslash = false;
        $self->apostropheStringEscapeChar = "'";
        $self->doubleSlashComment = Context::COMMENT;
        $self->parenStarBlock = Context::COMMENT;
        $self->curlyBlock = Context::COMMENT;

        return $self;
    }

    public static function perl(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->heredoc = Context::STRING;
        $self->apostropheHeredoc = Context::STRING;
        $self->quoteHeredoc = Context::STRING;
        $self->backtickHeredoc = Context::STRING;

        $self->hashComment = Context::COMMENT;
        $self->perlBlockComment = Context::COMMENT;

        $self->dataSegment = Context::DATA;
        $self->endDataSegment = Context::DATA;

        return $self;
    }

    public static function php(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->phpHeredoc = Context::STRING;
        $self->phpApostropheHeredoc = Context::STRING;
        $self->phpQuoteHeredoc = Context::STRING;

        $self->hashComment = Context::COMMENT;
        $self->doubleSlashComment = Context::COMMENT;
        $self->slashStarBlock = Context::COMMENT;

        $self->slashDoubleStarBlock = Context::DOC;

        $self->phpDataSegment = Context::DATA;

        return $self;
    }

    public static function postscript(): self
    {
        $self = new self();
        $self->parenthesesString = Context::STRING;
        $self->percentComment = Context::COMMENT;

        return $self;
    }

    public static function powerShell(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->atApostropheBlock = Context::STRING;
        $self->atQuoteBlock = Context::COMMENT;
        $self->hashComment = Context::COMMENT;
        $self->arrowHashBlock = Context::COMMENT;

        return $self;
    }

    public static function prolog(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->percentComment = Context::COMMENT;
        $self->slashStarBlock = Context::COMMENT;

        return $self;
    }

    public static function python(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->tripleApostropheString = Context::STRING;
        $self->tripleQuoteString = Context::STRING;

        $self->hashComment = Context::COMMENT;

        return $self;
    }

    public static function r(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->hashComment = Context::COMMENT;

        return $self;
    }

    public static function racket(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->racketHeredoc = Context::STRING;
        $self->semicolonComment = Context::COMMENT;
        $self->hashPipeBlock = Context::COMMENT;

        return $self;
    }

    public static function ruby(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->heredoc = Context::STRING;
        $self->indentHeredoc = Context::STRING;
        $self->rubyIndentHeredoc = Context::STRING;

        $self->hashComment = Context::COMMENT;
        $self->rubyBlockComment = Context::COMMENT;

        $self->dataSegment = Context::DATA;

        return $self;
    }

    public static function rust(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;

        $self->hashComment = Context::COMMENT;
        $self->doubleSlashComment = Context::COMMENT;

        $self->tripleSlashComment = Context::DOC;
        $self->doubleSlashBangComment = Context::CODE;

        return $self;
    }

    public static function scala(): self
    {
        $self = new self();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->doubleSlashComment = Context::COMMENT;
        $self->slashStarBlock = Context::COMMENT;

        return $self;
    }

    public static function scheme(): self
    {
        $self = new self();
        $self->quoteString = Context::STRING;
        $self->semicolonComment = Context::COMMENT;
        $self->hashPipeBlock = Context::COMMENT;

        return $self;
    }

    public static function simula(): self
    {
        $self = new static();
        $self->apostropheString = Context::SKIP;
        $self->quoteString = Context::STRING;
        $self->bangComment = Context::COMMENT;

        return $self;
    }

    public static function smallTalk(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteSlashComment = Context::COMMENT;
        $self->quoteString = Context::COMMENT;
        $self->quoteStringBackslash = false;

        return $self;
    }

    public static function sql(): self
    {
        $self = new self();
        $self->quoteString = Context::CODE;
        $self->quoteStringBackslash = false;
        $self->quoteStringEscapeChar = '"';

        $self->apostropheString = Context::STRING;
        $self->apostropheStringBackslash = false;
        $self->apostropheStringEscapeChar = "'";

        $self->doubleDashComment = Context::COMMENT;

        return $self;
    }

    public static function mysql(): self
    {
        $self = self::sql();
        $self->backtickString = Context::CODE;

        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->quoteStringBackslash = true;
        $self->apostropheStringBackslash = true;

        return $self;
    }

    public static function swift(): self
    {
        return self::c();
    }

    public static function tcl(): self
    {
        $self = new self();
        $self->quoteString = Context::STRING;
        $self->curlyBlock = Context::STRING;
        $self->hashComment = Context::COMMENT;

        return $self;
    }

    public static function visualBasic(): self
    {
        return self::basic();
    }

    // templating languages --------------------------------------------------------------------------------------------

    public static function blade(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->doubleCurlyDoubleDashBlock = Context::COMMENT;
        $self->doubleCurlyBlock = Context::CODE . '/php';
        $self->atDoubleCurlyBlock = Context::CODE . '/php';
        $self->atTag = Context::CODE . '/php';
        self::setupXmlTags($self);

        return $self;
    }

    public static function django(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->curlyHashBlock = Context::COMMENT;
        $self->doubleCurlyBlock = Context::CODE . '/python';
        $self->curlyDollarBlock = Context::CODE . '/python';
        self::setupXmlTags($self);

        return $self;
    }

    public static function ejs(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->arrowPercentBlock = Context::CODE;

        return $self;
    }

    public static function html(): self
    {
        return self::xml();
    }

    public static function latex(): self
    {
        return self::tex();
    }

    public static function latte(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->curlyBlock = Context::CODE . '/php';
        $self->curlyStarBlock = Context::COMMENT;
        self::setupXmlTags($self);

        return $self;
    }

    public static function mustache(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->doubleCurlyBangBlock = Context::COMMENT;
        $self->doubleCurlyBlock = Context::CODE;
        $self->dollarDoubleCurlyBlock = Context::CODE;
        self::setupXmlTags($self);

        return $self;
    }

    public static function smarty(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->curlyBlock = Context::CODE . '/php';
        $self->curlyStarBlock = Context::COMMENT;
        self::setupXmlTags($self);

        return $self;
    }

    public static function tex(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->backslashTag = Context::CODE;
        $self->curlyBlock = Context::CODE;
        $self->squareBlock = Context::CODE;
        $self->percentComment = Context::COMMENT;

        return $self;
    }

    public static function twig(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->curlyHashBlock = Context::COMMENT;
        $self->doubleCurlyBlock = Context::CODE . '/php';
        $self->curlyDollarBlock = Context::CODE . '/php';
        self::setupXmlTags($self);

        return $self;
    }

    public static function volt(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->curlyHashBlock = Context::COMMENT;
        $self->doubleCurlyBlock = Context::CODE;
        $self->curlyDollarBlock = Context::CODE;
        self::setupXmlTags($self);

        return $self;
    }

    public static function xml(): self
    {
        $self = new self();
        $self->defaultContext = Context::STRING;
        $self->xmlBlockComment = Context::COMMENT;
        self::setupXmlTags($self);

        return $self;
    }

    private static function setupXmlTags(self $self): void
    {
        $self->xmlTagName = Context::CODE;
        $self->xmlTagValue = Context::STRING;
        $self->xmlAttrName = Context::CODE;
        $self->xmlAttrValue = Context::CODE; /// ???
    }

    // style languages -------------------------------------------------------------------------------------------------

    public static function css(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->doubleSlashComment = Context::COMMENT;
        $self->slashStarBlock = Context::COMMENT;

        return $self;
    }

    // configuration languages -----------------------------------------------------------------------------------------

    public static function ini(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->semicolonComment = Context::COMMENT;

        return $self;
    }

    public static function neon(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->hashComment = Context::COMMENT;

        return $self;
    }

    public static function yaml(): self
    {
        $self = new self();
        $self->apostropheString = Context::STRING;
        $self->quoteString = Context::STRING;
        $self->hashComment = Context::COMMENT;

        return $self;
    }

}
