<?php

namespace App\Core\Domain\Utils\Helpers;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Carbon\Carbon;

class DateHelper
{
    private static function now(): Carbon
    {
        return now();
    }

    private static function today(): Carbon
    {
        return self::now()->copy()->startOfDay();
    }

    public static function expirationToHuman(?Carbon $date, ?string $status=null): ?string
    {
        if (!$date) {
            return null;
        }

        $now = self::now();
        $isFinalized = $status === PaymentConceptStatus::FINALIZADO->value;
        if ($now->gte($date->copy()->endOfDay()) || $isFinalized) {
            return self::expiredText($date);
        }

        return self::remainingText($date);
    }

    public static function expiredText(Carbon $date): string
    {
        $days = self::today()->diffInDays($date->copy()->startOfDay(), false);
        $days = abs($days);

        if ($days == 0) return 'Expirado hoy';
        if ($days == 1) return 'Expirado ayer';
        if ($days < 7) return "Expirado hace {$days} días";

        $weeks = floor($days / 7);
        if ($weeks < 4) {
            $remainingDays = $days % 7;
            $text = "Expirado hace {$weeks} semana" . ($weeks > 1 ? 's' : '');
            if($remainingDays > 0)
            {
                $text .= " y {$remainingDays} día" . ($remainingDays > 1 ? 's':'');
            }
            return $text;
        }

        $months = floor($days / 30);
        $remainingDays = $days % 30;
        $text = "Expirado hace {$months} mes" . ($months > 1 ? 'es' : '');
        if($remainingDays > 0)
        {
            $text .= " y {$remainingDays} día" . ($remainingDays > 1 ? 's' : '');
        }
        return $text;
    }

    public static function remainingText(Carbon $date): string
    {
        $now = self::now();

        $seconds = $now->diffInSeconds($date->copy()->endOfDay());

        if ($seconds < 3600) {
            $minutes = max(1, ceil($seconds / 60));
            return "Vence en {$minutes} minuto" . ($minutes > 1 ? 's' : '');
        }

        if ($seconds < 86400) {
            $hours = ceil($seconds / 3600);
            return "Vence en {$hours} hora" . ($hours > 1 ? 's' : '');
        }

        $days = self::today()->diffInDays($date->copy()->startOfDay(), false);

        if ($days == 0) return 'Vence hoy';
        if ($days == 1) return 'Vence mañana';
        if ($days < 7) return "Vence en {$days} días";

        $weeks = floor($days / 7);
        if ($weeks < 4) {
            $remainingDays = $days % 7;
            $text = "Vence en {$weeks} semana" . ($weeks > 1 ? 's' : '');
            if ($remainingDays > 0) {
                $text .= " y {$remainingDays} día" . ($remainingDays > 1 ? 's' : '');
            }
            return $text;
        }

        $months = floor($days / 30);
        $remainingDays = $days % 30;
        $text = "Vence en {$months} mes" . ($months > 1 ? 'es' : '');
        if ($remainingDays > 0) {
            $text .= " y {$remainingDays} día" . ($remainingDays > 1 ? 's' : '');
        }
        return $text;
    }

    public static function expirationInfo(?Carbon $date, ?string $status=null): array
    {
        if (!$date) {
            return [
                'text' => null,
                'days' => null,
                'is_expired' => false,
                'is_today' => false,
                'urgency' => 'none',
            ];
        }
        $now = self::now();
        $days = self::today()->diffInDays($date->copy()->startOfDay(), false);
        $isFinalized = $status === PaymentConceptStatus::FINALIZADO->value;
        $isExpired = $isFinalized || $date->copy()->endOfDay()->lt($now);

        return [
            'text' => self::expirationToHuman($date, $status),
            'days' => $days,
            'is_expired' => $isExpired,
            'is_today' => $days == 0,
            'urgency' =>  self::urgencyLevel($days, $isExpired),
            'date_formatted' => $date->isoFormat('D [de] MMMM [de] YYYY'),
            'date_short' => $date->format('d/m/Y'),
        ];
    }

    public static function urgencyLevel(int $days, bool $isExpired): string
    {
        if ($days < 0 || $isExpired) return 'vencido';
        if ($days == 0) return 'vencimiento_hoy';
        if ($days <= 3) return 'urgencia_alta';
        if ($days <= 7) return 'urgencia_media';
        return 'urgencia_baja';
    }

    public static function daysUntilDeletion(?Carbon $deletedDate): ?int
    {
        if (!$deletedDate) {
            return null;
        }
        $forceDeleteDate = $deletedDate->copy()->addDays(30)->endOfDay();

        $days = self::today()->diffInDays($forceDeleteDate, false);

        return max(0, $days);

    }

}
