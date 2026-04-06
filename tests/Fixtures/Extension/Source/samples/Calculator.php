<?php

declare(strict_types=1);

namespace App;

class Calculator
{
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    public function subtract(int $a, int $b): int
    {
        return $a - $b;
    }

    public function multiply(int $a, int $b): int
    {
        return $a * $b;
    }

    public function divide(float $a, float $b): float
    {
        if (0.0 === $b) {
            throw new \DivisionByZeroError('Division by zero');
        }

        return $a / $b;
    }
}
