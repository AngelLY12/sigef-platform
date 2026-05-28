@extends('emails.layouts.base')

@section('title', 'Acción requerida para completar tu pago')

@section('header_title')
    {{ $headerTitle }}
@endsection

@section('greeting')
    Hola {{ $recipientName }}
@endsection

@section('message_intro')
    {{ $messageIntro }}
@endsection

@section('message_details')
    <p><strong>Monto:</strong> ${{ $amount }}</p>

    @if($paymentMethod === 'oxxo')
        <p><strong>Número de referencia:</strong> {{ $reference }}</p>

        <p>
            <strong>Voucher:</strong>
            <a href="{{ $url }}" target="_blank">Ver voucher</a>
        </p>

        <p>
            Tu pago será actualizado automáticamente una vez que completes la operación
            en la tienda OXXO.
        </p>

        <p>
            <strong>Tienes un tiempo límite de {{ $expirationDays }} días para realizar el pago.</strong>
        </p>
    @else
        <p><strong>Referencia:</strong> {{ $reference }}</p>

        <p>
            <strong>Instrucciones:</strong>
            <a href="{{ $url }}" target="_blank">Ver instrucciones</a>
        </p>

        <p>
            Tu pago será actualizado automáticamente una vez que la transferencia sea recibida.
        </p>
    @endif
@endsection

@section('message_footer')
    Si tienes alguna duda, revisa las instrucciones antes de realizar el pago.
@endsection
