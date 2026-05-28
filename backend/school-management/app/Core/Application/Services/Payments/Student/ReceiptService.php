<?php

namespace App\Core\Application\Services\Payments\Student;

use App\Models\Receipt;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ReceiptService
{
    public function generate(Receipt $receipt): string
    {
        $disk = Storage::disk('gcs');
        if ($receipt->file_path && $disk->exists($receipt->file_path)) {
            return $receipt->file_path;
        }

        $html = view('receipts.receipt', [
            'receipt' => $receipt,
            'qr' => $this->generateQR($receipt),
        ])->render();

        $path = "receipts/". $receipt->issued_at->format('Y/m')."/{$receipt->folio}.html";

        $result=$disk->put($path, $html, [
            'visibility' => 'public',
            'ContentType' => 'text/html; charset=UTF-8',
            'ContentDisposition' => 'inline; filename="'.$receipt->folio.'.html"',

        ]);

        if (!$result || !$disk->exists($path)) {
            throw new \Exception("No se pudo guardar el archivo ");
        }

        if ($receipt->file_path !== $path) {
            $receipt->update(['file_path' => $path]);
        }

        return $path;
    }

    public function generateQR(Receipt $receipt)
    {
        $payload = json_encode([
            'folio' => $receipt->folio,
            'hash' => hash_hmac('sha256', $receipt->folio, config('receipt.key'))
        ]);

        $token = base64_encode($payload);
        $token = urlencode($token);

        $url = route('receipts.verify', ['token' => $token]);
        return QrCode::size(120)
            ->color(1, 50, 55)
            ->margin(1)
            ->generate($url);
    }

}
