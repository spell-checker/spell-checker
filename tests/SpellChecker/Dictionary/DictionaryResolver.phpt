<?php declare(strict_types = 1);

namespace SpellChecker\Dictionary;

use Tester\Assert;

require __DIR__ . '/../../bootstrap.php';

$always = [
    'foo'
];

$byName = [
    '*.php' => 'en cs code',
    '*-sk.latte' => 'sk code',
    '*.latte' => 'cs code',
    '*cs.po' => 'skip',
    '*sk.po' => 'cs/msgid sk/msgstr',
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
    ['foo', 'cs', 'code', 'latte', 'html'],
    $resolver->getDictionariesForFileName('/some/path/some-file.latte')
);

Assert::same(
    ['foo', 'sk', 'code', 'latte', 'html'],
    $resolver->getDictionariesForFileName('/some/path/some-file-sk.latte')
);

Assert::same(
    [],
    $resolver->getDictionariesForFileName('/some/path/cs.po')
);

Assert::same(
    ['foo', 'cs/msgid', 'sk/msgstr'],
    $resolver->getDictionariesForFileName('/some/path/sk.po')
);
