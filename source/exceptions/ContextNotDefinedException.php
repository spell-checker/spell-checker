<?php declare(strict_types = 1);

namespace SpellChecker;

use Exception;
use Throwable;
use function sprintf;

class ContextNotDefinedException extends Exception
{

    private string $context;

    public function __construct(string $context, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Context "%s" is not defined in your configuration.', $context), 0, $previous);

        $this->context = $context;
    }

    public function getContext(): string
    {
        return $this->context;
    }

}
