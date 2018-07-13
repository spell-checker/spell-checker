<?php declare(strict_types = 1);

namespace SpellChecker;

use Dogma\Tools\Colors as C;
use Dogma\Tools\Configurator;
use Dogma\Tools\Console;
use SpellChecker\Dictionary\DictionaryCollection;
use SpellChecker\Dictionary\DictionaryResolver;
use SpellChecker\Heuristic\AddressDetector;
use SpellChecker\Heuristic\Base64ImageDetector;
use SpellChecker\Heuristic\BulletsDetector;
use SpellChecker\Heuristic\CssUnitsDetector;
use SpellChecker\Heuristic\DictionarySearch;
use SpellChecker\Heuristic\EscapeSequenceDetector;
use SpellChecker\Heuristic\FileNameDetector;
use SpellChecker\Heuristic\GarbageDetector;
use SpellChecker\Heuristic\IdentifiersDetector;
use SpellChecker\Heuristic\PrintfDetector;
use SpellChecker\Heuristic\SimpleHtmlDetector;
use SpellChecker\Heuristic\SqlTableShortcutDetector;
use SpellChecker\Parser\DefaultParserProvider;
use SpellChecker\Parser\LanguageResolver;
use SpellChecker\Parser\PlainTextParser;
use Tracy\Debugger;
use function class_exists;
use function count;
use function implode;
use function log;
use function memory_get_peak_usage;
use function microtime;
use function min;
use function number_format;
use function sprintf;

class SpellCheckerApplication
{

    /** @var \Dogma\Tools\Console */
    private $console;

    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    public function run(Configurator $config): void
    {
        try {
            // files
            $finder = new FileFinder($config->baseDir);
            $files = $finder->findFilesByConfig($config);

            // dictionaries
            $dictionaryResolver = new DictionaryResolver(
                $config->dictionaries ?? [],
                $config->dictionariesByFileName ?? [],
                $config->dictionariesByFileExtension ?? []
            );
            $checkFiles = $config->checkDictionaryFiles
                ? $config->dictionaryFilesToCheck ?? []
                : [];
            $dictionaries = new DictionaryCollection(
                $config->dictionaryDirectories ?? [],
                $config->dictionariesWithDiacritics ?? [],
                $checkFiles,
                $config->baseDir,
                $this->console
            );

            // parsers
            $languageResolver = new LanguageResolver($config->parsers ?? []);
            $plainTextParser = new PlainTextParser($config->irregularWords ?? []);
            $parserProvider = new DefaultParserProvider($plainTextParser, $languageResolver, $config);

            // heuristics
            $heuristics = [
                new AddressDetector($dictionaries, (bool) $config->ignoreUrls, (bool) $config->ignoreEmails),
                new DictionarySearch($dictionaries),
                new CssUnitsDetector(),
                new PrintfDetector(),
                new EscapeSequenceDetector(),
                new SqlTableShortcutDetector(),
                new IdentifiersDetector($dictionaries),
                new FileNameDetector($dictionaries),
                new BulletsDetector(),
                new SimpleHtmlDetector(),
                new GarbageDetector(),
                new Base64ImageDetector(),
            ];

            // run check
            $spellChecker = new SpellChecker(
                $parserProvider,
                $heuristics,
                $dictionaryResolver,
                (int) $config->maxErrors,
                $config->localIgnores ?: [],
                (bool) $config->checkLocalIgnores
            );

            $startTime = microtime(true);
            $result = $spellChecker->checkFiles($files, function (string $fileName, array $errors) {
                if (count($errors) === 0) {
                    $this->console->write('.');
                } else {
                    $log = number_format(min(log(count($errors), 2), 9), 0);
                    $this->console->write($log);
                }

                return $errors;
            });
            $totalTime = microtime(true) - $startTime;
            $peakMemoryUsage = memory_get_peak_usage(true) / (1024 * 1024);
            $this->console->ln()->writeLn(sprintf(' (%s s, %s MB)', number_format($totalTime, 3), $peakMemoryUsage));

            // show results
            $this->console->ln(2);
            Console::switchTerminalToUtf8();

            $formatter = new ResultFormatter($dictionaryResolver, $parserProvider, $finder->getBaseDir());
            $this->console->writeLn($formatter->summarize($result));
            if ($result->errorsFound()) {
                if ($config->topWords) {
                    $this->console->ln()->write($formatter->formatTopBlocksByDictionaries($result));
                }
                if ($config->short) {
                    $this->console->ln()->write($formatter->formatErrorsShort($result));
                } else {
                    $this->console->ln()->write($formatter->formatErrors($result));
                }
                if ($result->getErrorsCount() >= $config->maxErrors) {
                    $this->console->ln()->writeLn(sprintf('Check stopped after %d errors.', $config->maxErrors));
                }
            }
            if ($config->checkDictionaryFiles) {
                foreach ($dictionaries->getDictionaries() as $name => $dictionary) {
                    if (!$dictionary->isChecked()) {
                        continue;
                    }
                    $unusedWords = $dictionary->getUnusedWords();
                    if ($unusedWords !== []) {
                        $this->console->writeLn(C::red('Unused words in dictionary "' . $name . '"'));
                        $this->console->writeLn(implode(', ', $unusedWords));
                    }
                }
            }

            if ($result->errorsFound()) {
                exit(1);
            }
        } catch (\SpellChecker\FileSearchNotConfiguredException $e) {
            $this->console->writeLn(C::red('Nothing to check. Configure directories or files.'));
            exit(1);
        } catch (\Throwable $e) {
            $this->console->ln()->writeLn(C::white(sprintf('Error occurred while spell-checking: %s', $e->getMessage()), C::RED));
            if (class_exists(Debugger::class)) {
                Debugger::log($e);
                exit(1);
            } else {
                throw $e;
            }
        }
        $this->console->ln();
    }

}
