<?php declare(strict_types = 1);

namespace SpellChecker\Parser\UniversalParser;

use SpellChecker\Parser\Context;

/**
 * Activate features by assigning a Context to them
 */
class UniversalParserSettings
{

    // line comments ---------------------------------------------------------------------------------------------------

    // ' Basic, VisualBasic
    public $apostropheComments;

    // * 360 Assembly, ABAP, Cobol
    public $asteriskComments;

    // *> Cobol
    public $asteriskArrowComments;

    // \ Forth
    public $backslashComments;

    // ` 4D
    public $backtickComments;

    // :: Batch
    public $doubleColonComments;

    // ! Factor, Fortran, Simula
    public $exclamationComments;

    // # AppleScript, CoffeeScript, Bash, E, Neon, Perl, PHP, PowerShell, Python, R, Tcl
    public $hashComments;

    // ### CoffeeScript ###
    public $tripleHashBlockComments;

    // -- Ada, AppleScript, Eiffel, Elm, Lua, Haskel, SQL
    public $doubleHyphenComments;

    // % Erlang, LaTeX, MatLab, PostScript, Prolog
    public $percentComments;

    // " ABAP
    public $quoteComments;

    // "/ SmallTalk
    public $quoteSlashComments;

    // ; Assembly, Clojure, .ini, Racket, Scheme
    public $semicolonComments;

    // / Cobol
    public $slashComments;

    // // ActionScript, C, C++, C#, CSS, D, Dart, F#, Go, Java, JavaScript, Kotlin, PHP, Rust, Scala, Swift
    public $doubleSlashComments;

    // /// Rust
    public $tripleSlashComments;

    // //! Rust
    public $slashSlashBangComments;

    // block comments --------------------------------------------------------------------------------------------------

    // /* ActionScript, C, C++, C#, CSS, Dart, D, Java, JavaScript, PHP, Prolog, Kotlin, SAS, Scala, Swift */
    public $cBlockComments;

    // /** Java, Kotlin, PHP, Rust */
    public $docBlockComments;

    // { Pascal }
    public $curlyBracketBlockComments;

    // /+ D +/
    public $dBlockComments;

    // {- Elm, Haskel -}
    public $haskelBlockComments;

    // {* Latte *}
    public $latteBlockComments;

    // #| Lisp, Racket, Scheme |#
    public $lispBlockComments;

    // --[[ Lua ]]
    public $luaBlockComments;

    // (* AppleScript, F#, Mathematica, Modula, Oberon, OCaml, Pascal *)
    public $pascalBlockComments;

    // =whatever Perl =cut
    public $perlBlockComments;

    // <# PowerShell #>
    public $powerShellBlockComments;

    // =begin Ruby =end
    public $rubyBlockComments;

    // * SAS ;
    public $sasBlockComments;

    // {# Twig, Django #}
    public $twigBlockComments;

    // <!-- HTML, XML -->
    public $xmlBlockComments;

    // strings ---------------------------------------------------------------------------------------------------------

    // 'most languages'
    public $apostropheStrings = Context::STRING;
    public $apostropheStringsEscapeChar = '\\';

    // "most languages", comment in SmallTalk
    public $quoteStrings = Context::STRING;
    public $quoteStringsEscapeChar = '\\';

    // `MySQL` (names)
    public $backtickStrings;
    public $backtickStringsEscapeChar = '\\';

    // '''Python''', string or doc comment
    public $tripleApostropheStrings;

    // """Python""", string or doc comment
    public $tripleQuoteStrings;

    // tags ------------------------------------------------------------------------------------------------------------

    // <tag attr='value'>XML, HTML</tag>
    public $xmlTags; // Context::CODE
    public $xmlAttrNameContext = Context::CODE;
    public $xmlAttrValuesContext = Context::STRING;

    // constructors ----------------------------------------------------------------------------------------------------

    public static function java(): self
    {
        $self = new self;
        $self->doubleSlashComments = Context::COMMENT;
        $self->cBlockComments = Context::COMMENT;
        $self->docBlockComments = Context::DOC;

        return $self;
    }

    public static function kotlin(): self
    {
        return self::java();
    }

    public static function javascript(): self
    {
        return self::java();
    }

    public static function javascriptJsx(): self
    {
        $self = self::java();
        $self->xmlTags = Context::CODE;

        return $self;
    }

    public static function c(): self
    {
        $self = new self;
        $self->doubleSlashComments = Context::COMMENT;
        $self->cBlockComments = Context::COMMENT;

        return $self;
    }

    public static function swift(): self
    {
        return self::c();
    }

    public static function php(): self
    {
        $self = new self;
        $self->hashComments = Context::COMMENT;
        $self->doubleSlashComments = Context::COMMENT;
        $self->cBlockComments = Context::COMMENT;
        $self->docBlockComments = Context::DOC;

        return $self;
    }

    public static function python(): self
    {
        $self = new self;
        $self->hashComments = Context::COMMENT;
        $self->tripleApostropheStrings = Context::DOC;
        $self->tripleQuoteStrings = Context::DOC;

        return $self;
    }

    public static function perl(): self
    {
        $self = new self;
        $self->hashComments = Context::COMMENT;
        $self->perlBlockComments = Context::COMMENT;

        return $self;
    }

    public static function ruby(): self
    {
        $self = new self;
        $self->hashComments = Context::COMMENT;
        $self->rubyBlockComments = Context::COMMENT;

        return $self;
    }

    public static function lua(): self
    {
        $self = new self;
        $self->doubleHyphenComments = Context::COMMENT;
        $self->luaBlockComments = Context::COMMENT;

        return $self;
    }

    public static function rust(): self
    {
        $self = new self;
        $self->hashComments = Context::COMMENT;
        $self->doubleSlashComments = Context::COMMENT;
        $self->tripleSlashComments = Context::DOC;
        $self->slashSlashBangComments = Context::CODE;

        return $self;
    }

    public static function go(): self
    {
        $self = new self;
        $self->doubleSlashComments = Context::COMMENT;

        return $self;
    }

    public static function sql(): self
    {
        $self = new self;
        $self->doubleHyphenComments = Context::COMMENT;
        $self->quoteStrings = Context::CODE;

        return $self;
    }

    public static function mysql(): self
    {
        $self = new self;
        $self->doubleHyphenComments = Context::COMMENT;
        $self->quoteStrings = Context::STRING;
        $self->backtickStrings = Context::CODE;

        return $self;
    }

}
