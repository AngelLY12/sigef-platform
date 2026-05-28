<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportFinishedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public array $importResult
    )
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Import finalizado',
            'message' => "Import de datos finalizado, a continuación veras un resúmen.",
            'details' => $this->buildImportMessage(),
            'type' => 'import_finished'
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => get_class($this),
            'data' => $this->toDatabase($notifiable),
            'read_at' => null,
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }


    private function buildImportMessage(): string
    {
        if (empty($this->importResult['summary'])) {
            return $this->importResult['message']
                ?? 'El import finalizó, pero no se pudo generar el resumen.';
        }

        $summary = $this->importResult['summary'];
        $errors  = $this->importResult['errors'] ?? [];
        $warnings = $this->importResult['warnings'] ?? [];

        $lines = [];

        $lines[] = 'RESUMEN DE IMPORTACIÓN';
        $lines[] = str_repeat('-', 40);
        $lines[] = "Filas recibidas: {$summary['total_rows_received']}";
        $lines[] = "Filas procesadas: {$summary['rows_processed']}";
        $lines[] = "Insertadas: {$summary['rows_inserted']}";
        $lines[] = "Fallidas: {$summary['rows_failed']}";
        $lines[] = "Tasa de éxito: {$summary['success_rate']}%";

        if (($warnings['total_warnings'] ?? 0) > 0) {
            $lines[] = "Advertencias: {$warnings['total_warnings']}";
            $warningList = array_slice($warnings['list'] ?? [], 0, 3);
            foreach ($warningList as $index => $warning) {
                $lines[] = "   {$index}. {$warning['message']}";
            }
        }

        if (($errors['total_errors'] ?? 0) > 0) {
            $lines[] = "Errores: {$errors['total_errors']}";
            $errorList = array_slice($errors['row_errors'] ?? [], 0, 5);
            foreach ($errorList as $index => $error) {
                $lines[] = "   {$index}. Fila {$error['row_number']}: {$error['message']}";
            }
            if (count($errors['row_errors'] ?? []) > 5) {
                $lines[] = "   ... y " . (count($errors['row_errors']) - 5) . " errores más";
            }
        }

        $lines[] = str_repeat('-', 40);
        $lines[] = ($this->importResult['timestamp'] ?? now()->format('d/m/Y H:i:s'));

        return implode("\n", $lines);

    }
}
