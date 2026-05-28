<?php

namespace App\Core\Domain\Utils\Helpers;

use App\Models\Payment;
use Illuminate\Support\Str;

class Folio
{

    public static function generateReceiptFolio(string $conceptName, int $receiptId): string
    {
        $conceptCode =  collect(explode(' ', $conceptName))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');
        $conceptCode = substr(str_pad($conceptCode, 3, 'X', STR_PAD_RIGHT), 0, 3);
        $dateCode = now()->format('mY');
        $correlative = str_pad($receiptId,10,'0',STR_PAD_LEFT);
        return "REC-{$conceptCode}-{$dateCode}-{$correlative}";
    }

}
