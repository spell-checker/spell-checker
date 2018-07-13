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
    // data segments
    public const DATA = 'data';

    // PHP: everything outside the script markers <?php
    public const HTML = 'html';

    // Gettext: translated strings
    public const TRANSLATION = 'trans';

    // skip words marked with this contexts
    public const SKIP = 'skip';

}
