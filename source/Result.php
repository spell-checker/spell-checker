<?php declare(strict_types = 1);

namespace SpellChecker;

use function array_keys;
use function count;

class Result
{

    /** @var Word[][] */
    private $errors;

    /** @var int */
    private $errorsCount;

    /**
     * @param Word[][] $errors
     * @param int $errorsCount
     */
    public function __construct(array $errors, int $errorsCount)
    {
        $this->errors = $errors;
        $this->errorsCount = $errorsCount;
    }

    public function errorsFound(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * @return Word[][]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorsCount(): int
    {
        return $this->errorsCount;
    }

    public function getFilesCount(): int
    {
        return count($this->errors);
    }

    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        return array_keys($this->errors);
    }

}
