<?php declare(strict_types = 1);

use Dogma\Str;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$path = $argv[1] ?? null;
if ($path === null) {
    fwrite(STDERR, "Usage: php strip-diacritics.php <dictionary-file>\n");
    exit(1);
}

// .dic -> .dia
$output = fopen(substr($path, 0, -1) . 'a', 'w');
$words = [];
foreach (explode("\n", file_get_contents($path)) as $word) {
    if ($word === '' || $word[0] === '#') {
        continue;
    }

    $stripped = Str::removeDiacritics($word);
    if ($stripped !== $word) {
        $words[$stripped] = true;
    }
}

foreach ($words as $word => $foo) {
    fwrite($output, $word . "\n");
}
