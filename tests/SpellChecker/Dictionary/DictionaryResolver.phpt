<?php declare(strict_types = 1);
// spell-check-ignore: zlavomat

namespace SpellChecker\Dictionary;

use Tester\Assert;

require '../../bootstrap.php';

$always = [
    'foo'
];

$byName = [
    '*.php' => 'en cs code',
    '*.js' => 'en cs code',
    '*.sql' => 'en cs sk code',
    '*-slevomat.latte' => 'cs code',
    '*-zlavomat.latte' => 'sk code',
    '*.latte' => 'cs code',
    '*.styl' => 'en css',
    '*.neon' => 'en',
    '*cs_CZ.po' => 'cs po code',
    '*sk_SK.po' => 'cs sk po code',
];

$byExtension = [
    'php' => 'php',
    'js' => 'js',
    'sql' => 'sql',
    'latte' => 'latte html',
];

$resolver = new DictionaryResolver($always, $byName, $byExtension);

Assert::same(
    ['foo', 'en', 'cs', 'code', 'php'],
    $resolver->getDictionariesForFileName('/some/path/file.php')
);

Assert::same(
    ['foo', 'sk', 'code', 'latte', 'html'],
    $resolver->getDictionariesForFileName('/some/path/some-file-zlavomat.latte')
);
