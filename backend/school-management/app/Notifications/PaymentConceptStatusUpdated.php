<?php

namespace App\Notifications;

use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConceptStatusUpdated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private array $concept,
        private string $oldStatus,
        private string $newStatus
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'payment_concept_status_changed',
            'concept_id' => $this->concept['id'],
            'concept_name' => $this->concept['concept_name'],
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'amount' => $this->concept['amount'],
            'applies_to' => $this->concept['applies_to']->value,
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'status_transition' => "{$this->oldStatus}_to_{$this->newStatus}",
            'timestamp' => now()->toISOString(),
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

    private function getTitle(): string
    {
        $titles = [
            PaymentConceptStatus::ACTIVO->value => [
                PaymentConceptStatus::FINALIZADO->value => 'Concepto finalizado',
                PaymentConceptStatus::DESACTIVADO->value => 'Concepto pausado',
                PaymentConceptStatus::ELIMINADO->value => 'Concepto eliminado',
            ],
            PaymentConceptStatus::FINALIZADO->value => [
                PaymentConceptStatus::ACTIVO->value => 'Concepto reactivado',
            ],
            PaymentConceptStatus::DESACTIVADO->value => [
                PaymentConceptStatus::ACTIVO->value => 'Concepto reactivado',
            ],
            PaymentConceptStatus::ELIMINADO->value => [
                PaymentConceptStatus::ACTIVO->value => 'Concepto restaurado',
            ],
        ];

        return $titles[$this->oldStatus][$this->newStatus]
            ?? 'Estado de concepto actualizado';

    }

    private function getMessage(): string
    {
        $messages = [
            PaymentConceptStatus::ACTIVO->value => [
                PaymentConceptStatus::FINALIZADO->value =>
                    "El concepto '{$this->concept['concept_name']}' ha sido FINALIZADO. Ya no se aceptan más pagos.",
                PaymentConceptStatus::DESACTIVADO->value =>
                    "El concepto '{$this->concept['concept_name']}' ha sido PAUSADO temporalmente.",
                PaymentConceptStatus::ELIMINADO->value =>
                    "El concepto '{$this->concept['concept_name']}' ha sido ELIMINADO del sistema.",
            ],
            PaymentConceptStatus::ACTIVO->value => [
                'default' => "El concepto '{$this->concept['concept_name']}' está ahora ACTIVO y disponible para pago.",
            ],
        ];

        $message = $messages[$this->oldStatus][$this->newStatus]
            ?? $messages[$this->newStatus]['default']
            ?? "El concepto '{$this->concept['concept_name']}' cambió de {$this->oldStatus} a {$this->newStatus}.";

        if ($this->newStatus === PaymentConceptStatus::ACTIVO->value
            && isset($this->concept['end_date'])) {
            $endDate = $this->concept['end_date'] instanceof \Carbon\Carbon
                ? $this->concept['end_date']
                : \Carbon\Carbon::parse($this->concept['end_date']);
            $message .= " Fecha límite: " . $endDate->format('d/m/Y');
        }

        return $message;
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
}
