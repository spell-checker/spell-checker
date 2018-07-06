<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Tools\Colors as C;
use Dogma\Tools\Configurator;
use Dogma\Tools\Console;
use Tracy\Debugger;

require_once __DIR__ . '/src/Colors.php';
require_once __DIR__ . '/src/Console.php';
$console = new Console();

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // run separately
    require_once __DIR__ . '/vendor/autoload.php';

    $logDir = __DIR__ . '/log';
    if (!is_dir($logDir)) {
        mkdir($logDir);
    }
    Debugger::enable(Debugger::DEVELOPMENT, $logDir);
    Debugger::$maxDepth = 8;
    Debugger::$maxLength = 1000;
    Debugger::$showLocation = true;
} elseif (file_exists(dirname(dirname(__DIR__)) . '/autoload.php')) {
    // run from other app
    require_once dirname(dirname(__DIR__)) . '/autoload.php';
    if (Debugger::$logDirectory === null) {
        $logDir = getcwd() . '/log';
        if (!is_dir($logDir)) {
            mkdir($logDir);
        }
        Debugger::$logDirectory = $logDir;
    }
} else {
    $console->write(C::lcyan('Spell Checker'))->ln(2);
    $console->write(C::white('Run `composer install` to install dependencies.', C::RED));
    die();
}

$arguments = [
        'Configuration:',
    'config' =>         ['c', Configurator::VALUES, 'configuration files', 'paths'],
    'memoryLimit' =>    ['m', Configurator::VALUE, 'memory limit'],
    'use' =>            ['', Configurator::VALUES, 'configuration profiles to use', 'profiles'],
    'debug' =>          ['', Configurator::FLAG, 'show debug info'],
        'File selection:',
    'baseDir' =>        ['b', Configurator::VALUE, 'base directory for relative paths', 'path'],
    'files' =>          ['f', Configurator::VALUES, 'files to check', 'paths'],
    'directories' =>    ['d', Configurator::VALUES, 'directories to check', 'paths'],
    'extensions' =>     ['e', Configurator::VALUES, 'file extensions to check', 'extensions'],
        'Dictionaries:',
    'dictionaries' =>   ['D', Configurator::VALUES, 'dictionaries to use on all files', 'list'],
    'dictionariesByFileName' => ['n', Configurator::VALUES, 'file name pattern -> list of dictionaries', 'map'],
    'dictionariesByFileExtension' => ['x', Configurator::VALUES, 'file extension -> list of dictionaries', 'map'],
    'dictionariesWithDiacritics' => ['', Configurator::VALUES, 'dictionaries containing words with diacritics', 'list'],
    'dictionaryDirectories' => ['', Configurator::VALUES, 'paths to directories containing dictionaries', 'paths'],
        'Other:',
    'localIgnores'      => ['', Configurator::VALUES, 'file name pattern -> list of locally ignored words', 'map'],
    'checkLocalIgnores' => ['', Configurator::FLAG, 'check if all local exceptions are used'],
    'checkDictionaryFiles' => ['', Configurator::FLAG, 'check configured dictionary file for unused word'],
    'dictionaryFilesToCheck' => ['', Configurator::VALUES, 'list of user dictionaries to check for unused words', 'names'],
    'short' =>          ['s', Configurator::FLAG, 'shorter output with only file and list of words'],
    'topWords' =>       ['t', Configurator::FLAG, 'output list of top misspelled words'],
    'maxErrors' =>      ['', Configurator::VALUE, 'maximum number of error before check stops', 'number'],
    'wordsParserExceptions' => ['', Configurator::VALUES, 'irregular words', 'words'],
    'ignoreUrls' =>     ['', Configurator::FLAG, 'ignore all words from URL addresses'],
    'ignoreEmails' =>   ['', Configurator::FLAG, 'ignore all words from email addresses'],
        'Help:',
    'help' =>           ['h', Configurator::FLAG_VALUE, 'show help', 'command'],
    'license' =>        ['', Configurator::FLAG, 'show license'],
        'CLI output:',
    'noColors' =>       ['C', Configurator::FLAG, 'without colors'],
    'noLogo' =>         ['L', Configurator::FLAG, 'without logo'],
];
$defaults = [
    'config' => [strtr(__DIR__, '\\', '/') . '/build/spell-checker.neon'],
    'maxErrors' => SpellChecker::DEFAULT_MAX_ERRORS,
    'wordsParserExceptions' => ['PHPUnit'],
    'ignoreUrls' => false,
    'ignoreEmails' => false,
];
$config = new Configurator($arguments, $defaults);
$config->loadCliArguments();

$console->debug = $config->debug;

if ($config->noColors) {
    C::$off = true;
}

if (!$config->noLogo) {
    $console->writeLn(C::lgreen("             _ _        _           _           "));
    $console->writeLn(C::lgreen(" ___ ___ ___| | |   ___| |_ ___ ___| |_ ___ ___ "));
    $console->writeLn(C::lgreen("|_ -| . | -_| | |  |  _|   | -_|  _| '_| -_|  _|"));
    $console->writeLn(C::lgreen("|___|  _|___|_|_|  |___|_|_|___|___|_,_|___|_|  " . C::lcyan(' by @paranoiq')));
    $console->writeLn(C::lgreen("    |_|                                         "));
    $console->ln();
}

if ($config->help === true || (!$config->hasValues() && (!$config->config))) {
    $console->write('Usage: php spell-checker.php [options]')->ln(2);
    $console->write($config->renderHelp());
    exit;
} elseif ($config->license || $config->help === 'license') {
    $console->writeFile(__DIR__ . '/license.md');
    exit;
}

foreach ($config->config as $path) {
    $config->loadConfig($path);
}

if ($config->memoryLimit !== null) {
    if (!preg_match('#^\d+[kMG]?$#i', $config->memoryLimit)) {
        $console->writeLn(C::white(sprintf('Invalid memory limit format "%s".', $config->memoryLimit), C::RED));
        return 1;
    }
    if (ini_set('memory_limit', $config->memoryLimit) === false) {
        $console->writeLn(C::white(sprintf('Memory limit "%s" cannot be set.', $config->memoryLimit), C::RED));
        return 1;
    }
}

$application = new SpellCheckerApplication($console);
$application->run($config);

