<?php declare(strict_types = 1);
// spell-check-ignore: zlavomat

namespace SpellChecker\Dictionary;

use Tester\Assert;

require '../../bootstrap.php';

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
    'php' => ['en', 'cs', 'code'],
    'js' => ['en', 'cs', 'code'],
    'sql' => ['en', 'cs', 'sk', 'code'],
    'latte-cs' => ['cs', 'code'],
    'latte-sk' => ['sk', 'code'],
    'stylus' => ['en', 'css'],
    'neon' => ['en'],
    'po-cs-cs' => ['cs', 'po', 'code'],
    'po-cs-sk' => ['cs', 'sk', 'po', 'code'],
];

$byExtension = [
    'php' => ['php'],
    'js' => ['js'],
    'sql' => ['sql'],
    'latte' => ['latte', 'html'],
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

$resolver = new DictionaryResolver($filePatterns, $contexts, $byExtension);

Assert::same(
    ['en', 'cs', 'code', 'php'],
    $resolver->getDictionariesForFileName('/some/path/file.php')
);

Assert::same(
    ['sk', 'code', 'latte', 'html'],
    $resolver->getDictionariesForFileName('/some/path/some-file-zlavomat.latte')
);
