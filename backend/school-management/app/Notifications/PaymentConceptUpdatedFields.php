<?php

namespace App\Notifications;

use App\Core\Domain\Utils\Helpers\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConceptUpdatedFields extends Notification
{
    use Queueable;
    protected array $paymentConcept;
    protected array $changes;

    public function __construct(array $paymentConcept, array $changes)
    {
        $this->paymentConcept = $paymentConcept;
        $this->changes = $changes;
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

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->getTitle(),
            'message' => $this->getMessage($notifiable),
            'concept_id' => $this->paymentConcept['id'],
            'concept_name' => $this->paymentConcept['concept_name'],
            'amount' => $this->paymentConcept['amount'],
            'start_date' => $this->paymentConcept['start_date']?->toISOString(),
            'end_date' => $this->paymentConcept['end_date']?->toISOString(),
            'changes' => $this->getFilteredChanges(),
            'type' => 'payment_concept_changed',
            'priority' => $this->getPriority(),
            'created_at' => now()->toISOString(),
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
        return $this->toDatabase($notifiable);
    }

    private function getTitle(): string
    {
        return "Actualización del concepto de pago";
    }

    private function getMessage(object $notifiable): string
    {
        $conceptName = $this->paymentConcept['concept_name'];
        $amount = Money::from($this->paymentConcept['amount'])->finalize();
        $userName = $notifiable->name ?? 'Estudiante';
        $changeMessages = [];
        foreach ($this->changes as $change) {
            if ($change['field'] === 'amount') {
                $oldAmount = Money::from($change['old'])->finalize();
                $newAmount = Money::from($change['new'])->finalize();
                $changeMessages[] = "Monto cambiado de {$oldAmount} a {$newAmount} MXN";
            } elseif ($change['field'] === 'concept_name') {
                $changeMessages[] = "Nombre cambiado de '{$change['old']}' a '{$change['new']}'";
            } elseif ($change['field'] === 'start_date') {
                $oldDate = \Carbon\Carbon::parse($change['old'])->format('d/m/Y');
                $newDate = \Carbon\Carbon::parse($change['new'])->format('d/m/Y');
                $changeMessages[] = "Fecha de inicio: {$oldDate} → {$newDate}";
            } elseif ($change['field'] === 'end_date') {
                $oldDate = \Carbon\Carbon::parse($change['old'])->format('d/m/Y');
                $newDate = \Carbon\Carbon::parse($change['new'])->format('d/m/Y');
                $changeMessages[] = "Fecha de fin: {$oldDate} → {$newDate}";
            } elseif ($change['field'] === 'description') {
                $changeMessages[] = "Descripción actualizada";
            }
        }

        $baseMessage = "Hola {$userName}, el concepto '{$conceptName}' ({$amount} MXN) ha sido actualizado.";
        if (!empty($changeMessages)) {
            $limitedChanges = array_slice($changeMessages, 0, 3);
            $baseMessage .= " Cambios: " . implode(', ', $limitedChanges);

            if (count($changeMessages) > 3) {
                $baseMessage .= ", y otros cambios más.";
            } else {
                $baseMessage .= ".";
            }
        }

        return $baseMessage;
    }
    private function getFilteredChanges(): array
    {
        $relevantFields = ['concept_name', 'amount', 'start_date', 'end_date'];

        return array_filter($this->changes, function($change) use ($relevantFields) {
            return in_array($change['field'], $relevantFields);
        });
    }

    private function getPriority(): string
    {
        foreach ($this->changes as $change) {
            if ($change['field'] === 'amount' && bccomp($change['new'], $change['old']) === 1) {
                return 'high';
            }
            if ($change['field'] === 'start_date' || $change['field'] === 'end_date') {
                return 'high';
            }
        }

        return 'medium';
    }
}
