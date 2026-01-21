<?php declare(strict_types = 1);

namespace SpellChecker;

use Exception;
use Throwable;
use function sprintf;

class DictionaryFileNotReadableException extends Exception
{

    private string $fileName;

    public function __construct(string $fileName, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Dictionary file "%s" does not exist or is not readable.', $fileName), 0, $previous);

        $this->fileName = $fileName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

}
