<?php

namespace App\Http\Requests\Admin\Events;

use App\Core\Application\DTO\Request\Events\PaymentEvent\PaymentEventFilters;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class PaymentEventIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'forceRefresh' => filter_var($this->forceRefresh, FILTER_VALIDATE_BOOLEAN),
            'page' => $this->page !== null ? (int) $this->page : 1,
            'perPage' => $this->perPage !== null ? (int) $this->perPage : 20,
            'paymentId' => $this->paymentId !== null ? (int) $this->paymentId : null,
            'processed' => $this->input('processed') !== null
                ? filter_var(
                    $this->input('processed'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                )
                : null,
            'stripePaymentIntentId' => $this->stripePaymentIntentId !== null ? trim($this->stripePaymentIntentId) : null,
            'stripeSessionId' => $this->stripeSessionId !== null ? trim($this->stripeSessionId) : null,
            'from' => $this->from !== null ? trim($this->from) : null,
            'to' => $this->to !== null ? trim($this->to) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'forceRefresh' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1',],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:100',],
            'paymentId' => ['sometimes', 'nullable', 'integer', 'min:1',],
            'eventType' => ['sometimes', 'nullable', Rule::enum(PaymentEventType::class),],
            'processed' => ['sometimes', 'nullable', 'boolean',],
            'stripePaymentIntentId' => ['sometimes', 'nullable', 'string', 'max:100',],
            'stripeSessionId' => ['sometimes', 'nullable', 'string', 'max:100',],
            'from' => ['sometimes', 'nullable', 'date',],
            'to' => ['sometimes', 'nullable', 'date', 'after_or_equal:from',],
        ];
    }

    public function messages(): array
    {
        return [
            'forceRefresh.boolean' => 'El valor de recarga debe ser verdadero o falso.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser como mínimo 1.',
            'perPage.integer' => 'El número de registros por página debe ser un número entero.',
            'perPage.min' => 'El número de registros por página debe ser como mínimo 1.',
            'perPage.max' => 'El número máximo de registros por página es 100.',
            'paymentId.integer' => 'El ID del pago debe ser un número entero.',
            'paymentId.min' => 'El ID del pago debe ser mayor que 0.',
            'eventType.enum' => 'El tipo de evento de pago seleccionado no es válido.',
            'stripePaymentIntentId.string' => 'El ID de pago de stripe debe ser una cadena de texto.',
            'stripePaymentIntentId.max' => 'El ID de pago de stripe no puede superar los 100 caracteres.',
            'stripeSessionId.string' => 'El ID de sesión de stripe debe ser una cadena de texto.',
            'stripeSessionId.max' => 'El ID de sesión de stripe no puede superar los 100 caracteres.',
            'from.date' => 'La fecha inicial no tiene un formato válido.',
            'to.date' => 'La fecha final no tiene un formato válido.',
            'to.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'forceRefresh' => 'recarga',
            'page' => 'página',
            'perPage' => 'registros por página',
            'paymentId' => 'pago',
            'eventType' => 'tipo de evento',
            'processed' => 'procesado',
            'stripePaymentIntentId' => 'ID de pago',
            'stripeSessionId' => 'ID de session',
            'from' => 'fecha inicial',
            'to' => 'fecha final',
        ];
    }
    public function toFilters(): PaymentEventFilters
    {
        return PaymentEventFilters::create(
            page: $this->integer('page', 1),
            perPage: $this->integer('perPage', 20),
            paymentId: $this->input('paymentId'),
            eventType: $this->filled('eventType')
                ? PaymentEventType::from($this->input('eventType'))
                : null,
            processed: $this->input('processed'),
            stripePaymentIntentId: $this->input('stripePaymentIntentId'),
            stripeSessionId: $this->input('stripeSessionId'),
            from: $this->filled('from')
                ? Carbon::parse($this->input('from'))->startOfDay()
                : null,

            to: $this->filled('to')
                ? Carbon::parse($this->input('to'))->endOfDay()
                : null,
        );
    }
}
