<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsbnRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The ISBN must be a string.');
            return;
        }

        $raw = strtoupper(trim($value));
        $raw = str_replace([' ', '-'], '', $raw);

        if ($this->isIsbn10($raw) || $this->isIsbn13($raw)) {
            return;
        }

        $fail('The ISBN must be a valid ISBN-10 or ISBN-13.');
    }

    private function isIsbn10(string $s): bool
    {
        if (! preg_match('/^[0-9]{9}[0-9X]$/', $s)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $digit = $s[$i] === 'X' ? 10 : (int) $s[$i];
            $sum += $digit * (10 - $i);
        }

        return $sum % 11 === 0;
    }

    private function isIsbn13(string $s): bool
    {
        if (! preg_match('/^[0-9]{13}$/', $s)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $digit = (int) $s[$i];
            $sum += $digit * (($i % 2 === 0) ? 1 : 3);
        }

        return $sum % 10 === 0;
    }
}

