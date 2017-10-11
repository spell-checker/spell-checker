<?php

namespace SpellChecker;

class Word
{

    /** @var string */
    public $word;

    /** @var string|null */
    public $block;

    /** @var int (in bytes, not characters!) */
    public $position;

    /** @var string */
    public $context;

    public function __construct(string $word, ?string $block, int $position)
    {
        $this->word = $word;
        $this->block = $block;
        $this->position = $position;
    }

}
