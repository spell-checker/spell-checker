<?php declare(strict_types = 1);

namespace SpellChecker;

class Word
{

    /** @var string */
    public $word;

    /** @var string|bool|null (true for unused ignores) */
    public $block;

    /** @var int (in bytes, not characters!) */
    public $position;

    /** @var int */
    public $rowNumber;

    /** @var int */
    public $rowStart;

    /** @var int */
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
        $this->rowNumber = $rowNumber;
        $this->rowStart = $rowStart;
        $this->rowEnd = $rowEnd;
    }

}
