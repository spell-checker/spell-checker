<?php declare(strict_types = 1);

namespace SpellChecker;

use Tester\Assert;

require '../bootstrap.php';

$filePatterns = [
    '*.php' => 'php',
    '*.js' => 'js',
    '*.sql' => 'sql',
    '*-slevomat.latte' => 'latte-cs',
    '*-zlavomat.latte' => 'latte-sk',
    '*.latte' => 'latte-cs',
    '*.styl' => 'stylus',
    '*.neon' => 'neon',
    '*cs_CZ.po' => 'po-cs-cs',
    '*sk_SK.po' => 'po-cs-sk',
];

$contexts = [
    'php' => ['en', 'cs', 'php', 'php-exceptions', 'slevomat-exceptions'],
    'php/code' => ['en', 'php', 'php-exceptions'],
    'php/string' => ['en', 'cs', 'sql', 'slevomat-exceptions'],
    'php/comment' => ['en', 'cs', 'slevomat-exceptions'],

    'js' => ['en', 'cs', 'js', 'js-exceptions', 'slevomat-exceptions'],
    'js/code' => ['en', 'js', 'js-exceptions'],
    'js/string' => ['en', 'cs', 'slevomat-exceptions'],
    'js/comment' => ['en', 'cs', 'slevomat-exceptions'],

    'sql' => ['en', 'cs', 'sk', 'sql', 'slevomat-exceptions'],
    'sql/code' => ['en', 'sql'],
    'sql/string' => ['en', 'cs', 'sk', 'slevomat-exceptions'],
    'sql/comment' => ['en', 'cs', 'slevomat-exceptions'],

    'latte-cs' => ['cs', 'html', 'latte', 'latte-exceptions'],
    'latte-sk' => ['sk', 'html', 'latte', 'latte-exceptions'],

    'stylus' => ['en', 'css'],

    'neon' => ['en'],

    'po-cs-cs' => ['cs', 'po', 'slevomat-exceptions'],
    'po-cs-sk' => ['cs', 'sk', 'po', 'slevomat-exceptions'],
];

$dictionaries = [
    'en',
    'cs',
    'sk',
    'php', // PHP keywords, function, classes...
    'php-exceptions', // exceptions for PHP code (from libraries etc.)
    'js', // JS keywords, functions, DOM names...
    'js-exceptions', // exceptions for JS code (from libraries etc.)
    'sql', //
    'html',
    'latte',
    'latte-exceptions', // exceptions for Latte code (custom macros etc.)
    'css',
    'slevomat-exceptions' // exceptions in text (company names, shortcuts etc.)
];

$resolver = new DictionaryResolver($filePatterns, $contexts);

Assert::same(
    ['en', 'cs', 'php', 'php-exceptions', 'slevomat-exceptions'],
    $resolver->getDictionariesForFileName('/some/path/file.php')
);

Assert::same(
    ['sk', 'html', 'latte', 'latte-exceptions'],
    $resolver->getDictionariesForFileName('/some/path/some-file-zlavomat.latte')
);
