@extends('emails.layouts.base')
@section('title','Cuenta creada')

@section('header_title')
    Cuenta creada
@endsection

@section('greeting')
    Hola {{ $name }}
@endsection

@section('message_intro')
    Hemos creado una cuenta para ti.
@endsection

@section('message_details')
    <p><strong>Tu contraseña es:</strong> {{ $password }}</p>
@endsection

@section('message_footer')
    Recuerda cambiar tu contraseña lo antes posible y verificar tu correo electrónico.
@endsection
