<?php declare(strict_types = 1);

namespace SpellChecker;

class Word
{

    /** @var string */
    public $word;

    /** @var string|null */
    public $block;

    /** @var int (in bytes, not characters!) */
    public $position;

    /** @var int */
    public $rowNumber;

    /** @var string */
    public $rowStart;

    /** @var string */
    public $rowEnd;

    /** @var string */
    public $row;

    public function __construct(
        string $word,
        ?string $block,
        int $position,
        int $rowNumber,
        int $rowStart,
        int $rowEnd
    )
    {
        $this->word = $word;
        $this->block = $block;
        $this->position = $position;
        $this->rowStart = $rowStart;
        $this->rowEnd = $rowEnd;
    }

}
