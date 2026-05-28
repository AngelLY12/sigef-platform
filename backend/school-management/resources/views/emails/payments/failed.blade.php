@extends('emails.layouts.base')

@section('title', 'Error en el pago')

@section('header_title')
    Error en el pago
@endsection

@section('greeting')
    Hola {{ $recipientName }}
@endsection

@section('message_intro')
    Ocurrió un problema al procesar tu pago.
@endsection

@section('message_details')
    <p><strong>Concepto:</strong> {{ $conceptName ?? 'No disponible' }}</p>
    <p><strong>Monto:</strong> ${{ $amount ?? '0.00' }}</p>
    <p><strong>Motivo del error:</strong></p>
    <p>{{ $error }}</p>
@endsection

@section('message_footer')
    Si el problema persiste, intenta nuevamente más tarde o ponte en contacto con la institución.
@endsection
