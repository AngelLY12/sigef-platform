@extends('emails.layouts.base')

@section('title', 'Pago validado')

@section('header_title')
    Pago validado
@endsection

@section('greeting')
    Hola {{ $recipientName }}
@endsection

@section('message_intro')
    Tu pago ha sido validado exitosamente.
@endsection

@section('message_details')
    <p><strong>Concepto:</strong> {{ $conceptName }}</p>
    <p><strong>Monto esperado:</strong> ${{ $amount }}</p>
    <p><strong>Monto recibido:</strong> ${{ $amountReceived }}</p>

    <p><strong>Método de pago:</strong> {{ $paymentMethodType ?? 'Desconocido' }}</p>
    <p><strong>Código de referencia:</strong> {{ $paymentIntentId }}</p>

    @if($reference)
        <p>
            <strong>Referencia de pago:</strong> {{ $reference }}
            <br>
            <small style="color: #2e7d5b">({{ $paymentMethodType }})</small>
        </p>
    @endif

    @if(!empty($paymentLegend))
        {!! $paymentLegend !!}
    @endif
@endsection

@section('message_footer')
    Gracias por realizar tu pago a tiempo. Podrás ver tu comprobante escolar en el historial de pagos, en detalles del pago
@endsection
