<?php declare(strict_types = 1);

namespace SpellChecker;

class FileSearchNotConfiguredException extends \Exception
{

    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct("File search is not configured. Fill either 'directories' or 'files' parameters, or both of them.", 0, $previous);
    }

}
