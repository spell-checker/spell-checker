<?php declare(strict_types = 1);

namespace SpellChecker;

/**
 * In principio erant Verba, et Verba erant ty vole.
 */
class Word
{

    public string $word;

    /** @var string|true|null (true for unused ignores) */
    public string|bool|null $block;

    /** @var int (in bytes, not characters!) */
    public int $position;

    public int $rowNumber;

    public ?string $context;

    public string $row;

    public function __construct(
        string $word,
        ?string $block,
        int $position,
        int $rowNumber,
        ?string $context = null,
    )
    {
        $this->word = $word;
        $this->block = $block;
        $this->position = $position;
        $this->rowNumber = $rowNumber;
        $this->context = $context;
    }

}
