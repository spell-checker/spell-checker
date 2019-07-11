<?php declare(strict_types = 1);

// spell-check-ignore: jambo, neno, hakuna matata, Enkai

// spell-check-ignore: Mchungaji

namespace SpellChecker;

class SpellCheckerTestClass
{

    public function jambo(): void
    {
        echo $this->validate('hakuna matata');
    }

    private function validate(string $neno): string
    {
        if ($neno === 'Enkai') {
            return 'Mchungaji hakusema!';
        }
        return $neno;
    }

}
