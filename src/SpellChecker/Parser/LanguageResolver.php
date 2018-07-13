<?php declare(strict_types = 1);

namespace SpellChecker\Parser;

class LanguageResolver
{

    /** @var string[] */
    private $extensions = [
        'apib' => ParserProvider::DEFAULT_PARSER,
        'applecsript' => 'appleScript',
        'as' => 'actionScript',
        'asm' => 'assembly',
        'b' => 'basic',
        'bas' => 'basic',
        'cbl' => 'cobol',
        'cl' => 'lisp',
        'clj' => 'clojure',
        'cob' => 'cobol',
        'coffee' => 'coffeeScript',
        'conf' => 'ini',
        'cpp' => 'cPlusPlus',
        'cs' => 'cSharp',
        'erl' => 'erlang',
        'f' => 'fortran',
        'for' => 'fortran',
        'fsx' => 'fSharp',
        'ftn' => 'fortran',
        'hs' => 'haskell',
        'i3' => 'modula',
        'ig' => 'modula',
        'js' => 'javaScript',
        'json' => ParserProvider::DEFAULT_PARSER,
        'kt' => 'kotlin',
        'kts' => 'kotlin',
        'lsp' => 'lisp',
        'm' => 'mathematica',
        'm3' => 'modula',
        'md' => ParserProvider::DEFAULT_PARSER,
        'mg' => 'modula',
        'ml' => 'oCaml',
        'mat' => 'matlab',
        'ob2' => 'oberon',
        'p' => 'prolog',
        'pas' => 'pascal',
        'phpt' => 'php',
        'pl' => 'perl',
        'pro' => 'prolog',
        'properties' => ParserProvider::DEFAULT_PARSER,
        'ps' => 'postScript',
        'ps1' => 'powerShell',
        'py' => 'python',
        'rb' => 'ruby',
        'rc' => 'rust',
        'rkt' => 'racket',
        'scm' => 'scheme',
        'sh' => 'bash',
        'sim' => 'simula',
        'st' => 'smallTalk',
        'ts' => 'javaScript',
        'vb' => 'visualBasic',
        'wl' => 'mathematica',
        'yml' => 'yaml',
    ];

    /**
     * @param string[] $names (string $extension => $name)
     */
    public function __construct(array $names)
    {
        foreach ($names as $extension => $name) {
            $this->extensions[$extension] = $name;
        }
    }

    public function getParserName(string $extension): ?string
    {
        return $this->extensions[$extension] ?? null;
    }

}
