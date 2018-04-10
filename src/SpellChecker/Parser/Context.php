<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

class Context
{

    // names of classes, functions, variables, tables, columns, tags, attributes...
    public const CODE = 'code';
    // string literals, original strings in Gettext
    public const STRING = 'string';
    // non-documentation comments
    public const COMMENT = 'comment';
    // documentation comments
    public const DOC = 'doc';

    // PHP: everything outside the script markers <?php
    public const HTML = 'html';
    // PHP: data after __halt_compiler() command
    public const DATA = 'data';

    // Gettext: translated strings
    public const TRANSLATION = 'trans';

}
