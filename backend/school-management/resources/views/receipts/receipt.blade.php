@extends('receipts.layouts.base')

@section('receipt_id', $receipt->id)
@section('folio', $receipt->folio)
@section('amount', $receipt->amount)

@section('title', 'Recibo de Pago - ' . $receipt->folio)

@section('content')
    <div class="folio-section">
        <span class="folio-label">Folio:</span>
        <span class="folio-value">{{ $receipt->folio }}</span>
    </div>

    <div class="student-card">
        <div class="student-header">
            <span class="student-label">ALUMNO</span>
        </div>
        <h2 class="student-name">{{ $receipt->payer_name }}</h2>
        <p class="student-email">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#4CA771" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
            {{ $receipt->payer_email }}
        </p>
    </div>

    <div class="details-grid">
        <div class="detail-item">
            <div class="detail-label">CONCEPTO</div>
            <div class="detail-value strong">{{ $receipt->concept_name }}</div>
        </div>

        <div class="detail-item">
            <div class="detail-label">FECHA DE PAGO</div>
            <div class="detail-value">
                @if(isset($receipt->metadata['payment_date']))
                    {{ \Carbon\Carbon::parse($receipt->metadata['payment_date'])->format('d/m/Y H:i') }}
                @else
                    {{ $receipt->issued_at->format('d/m/Y H:i') }}
                @endif
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">MONTO</div>
            <div class="detail-value strong">${{ $receipt->amount }} MXN</div>
        </div>

        <div class="detail-item">
            <div class="detail-label">FECHA DE EMISIÓN</div>
            <div class="detail-value">{{ $receipt->issued_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="amount-box">
        <p class="amount-label">TOTAL PAGADO</p>
        <p class="amount-value">
            <span>$</span>{{ $receipt->amount_received }} <span>MXN</span>
        </p>
    </div>

    @if($receipt->transaction_reference)
        <div class="reference-box">
            <div class="reference-label">Referencia de transacción</div>
            <div class="reference-value">{{ $receipt->transaction_reference }}</div>
        </div>
    @endif

    @include('receipts.partials.payment-details')
@endsection
