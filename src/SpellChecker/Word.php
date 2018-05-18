<?php declare(strict_types = 1);

namespace SpellChecker;

class Word
{

    /** @var string */
    public $word;

    /** @var string|true|null (true for unused ignores) */
    public $block;

    /** @var int (in bytes, not characters!) */
    public $position;

    /** @var int */
    public $rowNumber;

    /** @var string|null */
    public $context;

    /** @var string */
    public $row;

    public function __construct(
        string $word,
        ?string $block,
        int $position,
        int $rowNumber,
        ?string $context = null
    )
    {
        $this->word = $word;
        $this->block = $block;
        $this->position = $position;
        $this->rowNumber = $rowNumber;
        $this->context = $context;
    }

}
