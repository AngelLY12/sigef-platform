<?php

namespace App\Http\Requests\Admin\Events;

use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventFilters;
use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventHistoryFilters;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Status\EmailEventStatus;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailEventHistoryRequest extends FormRequest
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
            'eventType' => ['sometimes', 'nullable', Rule::enum(EmailEventType::class),],
            'status' => ['sometimes', 'nullable', Rule::enum(EmailEventStatus::class),],
            'sourceType' => ['sometimes', 'nullable', Rule::enum(EmailEventSourceType::class),],
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
            'eventType.enum' => 'El tipo de evento de correo seleccionado no es válido.',
            'status.enum' => 'El estado del evento de correo seleccionado no es válido.',
            'sourceType.enum' => 'El tipo de origen seleccionado no es válido.',
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
            'eventType' => 'tipo de evento',
            'status' => 'estado',
            'sourceType' => 'tipo de origen',
            'from' => 'fecha inicial',
            'to' => 'fecha final',
        ];
    }

    public function toFilters(): EmailEventHistoryFilters
    {
        return EmailEventHistoryFilters::create(
            page: $this->integer('page', 1),
            perPage: $this->integer('perPage', 20),
            eventType: $this->filled('eventType')
                ? EmailEventType::from($this->input('eventType'))
                : null,
            status: $this->filled('status')
                ? EMailEventStatus::from($this->input('status'))
                : null,
            sourceType: $this->filled('sourceType')
                ? EmailEventSourceType::from($this->input('sourceType'))
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
