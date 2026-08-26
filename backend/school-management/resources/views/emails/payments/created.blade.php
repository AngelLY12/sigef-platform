@extends('emails.layouts.base')

@section('title', 'Solicitud de pago registrada')

@section('header_title')
    Solicitud de pago registrada
@endsection

@section('greeting')
    Hola {{ $recipientName }}
@endsection

@section('message_intro')
    Hemos recibido correctamente tu solicitud de pago. El proceso de pago ha sido iniciado y se encuentra pendiente de validación.
@endsection

@section('message_details')
    <p><strong>Concepto:</strong> {{ $conceptName }}</p>
    <p><strong>Monto:</strong> ${{ $amount }}</p>
    <p><strong>Fecha de pago:</strong> {{ $createdAt }}</p>
    <p><strong>Sesión de pago:</strong> {{ $stripeSessionId }}</p>
    <p>
        <strong>URL de la sesión:</strong>
        <a href="{{ $url }}" target="_blank">
            Ver sesión
        </a>
    </p>
@endsection

@section('message_footer')
    Te notificaremos cuando el pago haya sido recibido y validado correctamente.
@endsection
