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

    public function looksLikeToken(): bool
    {
        $length = strlen($this->block);
        $upper = $length - strlen(preg_replace('/[A-Z]/', '', $this->block));
        $lower = $length - strlen(preg_replace('/[a-z]/', '', $this->block));
        $numbers = $length - strlen(preg_replace('/[0-9]/', '', $this->block));

        if ($upper >= $length / 4 && $lower > 0) {
            return true;
        }
        if ($numbers >= $length / 4) {
            return true;
        }
        if (count(preg_split('/[0-9]/', $this->block)) > 3) {
            return true;
        }
        return false;
    }

}
