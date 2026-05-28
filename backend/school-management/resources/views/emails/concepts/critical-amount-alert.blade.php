@extends('emails.layouts.base')

@section('title', 'Alerta de monto crítico')

@section('header_title')
    {{ $title }}
@endsection

@section('greeting')
    Hola {{ $fullName }}
@endsection

@section('message_intro')
    {{ $intro }}
@endsection

@section('message_details')
    <p>Se {{ $action }} un concepto con la siguiente información:</p>

    <p><strong>ID del Concepto:</strong> {{ $conceptId }}</p>
    <p><strong>Concepto:</strong> {{ $conceptName }}</p>
    <p><strong>Monto:</strong> ${{ $amount }}</p>
    <p><strong>Monto umbral:</strong> ${{ $threshold }}</p>
    <p><strong>Se excedió por:</strong> {{ $exceededBy }}</p>
@endsection

@section('message_footer')
    Asegúrate de verificar este concepto. Si no es un error, puedes ignorar este correo.
@endsection
