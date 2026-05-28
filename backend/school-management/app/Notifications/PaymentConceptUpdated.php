<?php

namespace App\Notifications;

use App\Core\Domain\Utils\Helpers\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class PaymentConceptUpdated extends Notification
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
            'action' => $this->determineMainChangeType(),
            'type' => 'payment_concept_changed',
            'priority' => 'high',
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
        $mainChangeType = $this->determineMainChangeType();

        return match($mainChangeType) {
            'created_concept' => 'Concepto de pago creado',
            'relation_update' => 'Actualización del concepto de pago',
            'relation_removed' => 'Concepto de pago ya no aplica',
            'applies_to_changed' => 'Nuevo concepto de pago aplicado',
            'exceptions_update' => 'Actualización de las excepciones del concepto de pago',
            default => 'Actualización de concepto de pago'

        };
    }

    private function determineMainChangeType(): string
    {
        foreach ($this->changes as $change) {
            if ($change['type'] === 'applies_to_changed') {
                return 'applies_to_changed';
            }
            if ($change['type'] === 'exceptions_update') {
                return 'exceptions_update';
            }
            if ($change['type'] === 'relation_update') {
                return 'relation_update';
            }
            if ($change['type'] === 'relation_removed') {
                return 'relation_removed';
            }
            if($change['type'] === 'created_concept'){
                return 'created_concept';
            }

        }

        return 'field_update';
    }


    private function getMessage(object $notifiable): string
    {
        $conceptName = $this->paymentConcept['concept_name'];
        $amount = Money::from($this->paymentConcept['amount'])->finalize();
        $userName = $notifiable->name;
        if (empty($this->changes)) {
            return "Hola {$userName}, te informamos que el concepto de pago '{$conceptName}' (monto: {$amount} MXN) ha sido actualizado.";
        }

        $changeMessages = [];
        $createdMessage=[];
        foreach ($this->changes as $change) {
            if($change['type'] === 'created_concept')
            {
                $startDate = $this->paymentConcept['start_date']?->format('d/m/Y') ?? 'N/A';
                $endDate = $this->paymentConcept['end_date']?->format('d/m/Y') ?? 'N/A';

                $createdMessage[] = "Nombre del concepto: {$conceptName}";
                $createdMessage[] = "Monto: {$amount} MXN";
                $createdMessage[] = "Válido del {$startDate} al {$endDate}";
            }

            if ($change['type'] === 'applies_to_changed') {
                $changeMessages[] = "El concepto ahora aplica a: {$change['new']}";
            } elseif ($change['type'] === 'relation_update') {
                switch($change['field']) {
                    case 'semesters':
                        $changeMessages[] = "El concepto ahora aplica a tu semestre";
                        break;
                    case 'applicant_tags':
                        $changeMessages[] = "El concepto ahora aplica a tu tag particular";
                        break;
                    case 'students':
                        $changeMessages[] = "El concepto de pago ahora aplica para ti";
                        break;
                    case 'careers':
                        $changeMessages[] = "El concepto de pago ahora aplica a tu carrera";
                        break;
                    case 'career_semester':
                    case 'careers_in_career_semester':
                    case 'semesters_in_career_semester':
                        $changeMessages[] = "El concepto ahora aplica a tu combinación de carrera/semestre";
                        break;
                }
            }
            elseif ($change['type'] === 'exceptions_update')
            {
                if (!empty($change['added']) && empty($change['removed'])) {
                    $changeMessages[] = "Fuiste agregado a las excepciones del concepto de pago, ya no aplica a ti";
                } elseif (!empty($change['removed']) && empty($change['added'])) {
                    $changeMessages[] = "Fuiste eliminado de las excepciones del concepto de pago, ahora aplica a ti y debes pagar";
                } else {
                    $changeMessages[] = "Tu estado en las excepciones del concepto ha cambiado";
                }
            }
            elseif ($change['type'] === 'relation_removed') {
                switch($change['field']) {
                    case 'semesters':
                        $changeMessages[] = "El concepto ya NO aplica a tu semestre";
                        break;
                    case 'applicant_tags':
                        $changeMessages[] = "El concepto ya NO aplica a tu tag particular";
                        break;
                    case 'students':
                        $changeMessages[] = "El concepto de pago ya NO aplica para ti";
                        break;
                    case 'careers':
                        $changeMessages[] = "El concepto de pago ya NO aplica a tu carrera";
                        break;
                    case 'career_semester':
                    case 'careers_in_career_semester':
                    case 'semesters_in_career_semester':
                        $changeMessages[] = "El concepto ya NO aplica a tu combinación de carrera/semestre";
                        break;
                    default:
                        $changeMessages[] = "El concepto ya NO aplica a ti";
                        break;
                }
            }
        }

        if(!empty($createdMessage))
        {
            $message = "Hola {$userName}, te informamos que hay un nuevo concepto de pago creado. ";
            $message .= "Detalles importantes: " . implode(", ", $createdMessage) . '.';
            return $message;
        }

        $baseMessage = "Hola {$userName}, te informamos que el concepto de pago '{$conceptName}' (monto: {$amount} MXN) ha sido actualizado.";
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
        $relevantTypes = [
            'relation_update',
            'applies_to_changed',
            'exceptions_update',
            'created_concept',
            'relation_removed'
        ];

        return array_values(array_filter(
            $this->changes,
            fn($change) => in_array($change['type'], $relevantTypes)
        ));
    }

}
