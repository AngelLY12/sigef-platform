<?php

namespace App\Http\Requests\Admin\Events;

use App\Core\Application\DTO\Request\Events\Reconciliation\ReconciliationEventFilters;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Enum\Events\Status\ReconciliationEventStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReconciliationEventIndexRequest extends FormRequest
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
            'sourceId' => $this->sourceId !== null ? trim($this->sourceId) : null,
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
            'status' => ['sometimes', 'nullable', Rule::enum(ReconciliationEventStatus::class),],
            'sourceType' => ['sometimes', 'nullable', Rule::enum(ReconciliationSourceType::class),],
            'sourceId' => ['sometimes', 'nullable', 'string', 'max:100',],
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
            'status.enum' => 'El estado del evento de reconciliación seleccionado no es válido.',
            'sourceType.enum' => 'El tipo de origen seleccionado no es válido.',
            'sourceId.string' => 'El ID de origen debe ser una cadena de texto.',
            'sourceId.max' => 'El ID de origen no puede superar los 100 caracteres.',
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
            'status' => 'estado',
            'sourceType' => 'tipo de origen',
            'sourceId' => 'ID de origen',
            'from' => 'fecha inicial',
            'to' => 'fecha final',
        ];
    }
    public function toFilters(): ReconciliationEventFilters
    {
        return ReconciliationEventFilters::create(
            page: $this->integer('page', 1),
            perPage: $this->integer('perPage', 20),
            paymentId: $this->input('paymentId'),
            sourceType: $this->filled('sourceType')
                ? ReconciliationSourceType::from($this->input('sourceType'))
                : null,
            sourceId: $this->input('sourceId'),
            status: $this->filled('status')
                ? ReconciliationEventStatus::from($this->input('status'))
                : null,
            from: $this->filled('from')
                ? Carbon::parse($this->input('from'))->startOfDay()
                : null,

            to: $this->filled('to')
                ? Carbon::parse($this->input('to'))->endOfDay()
                : null,
        );
    }
}
