@extends('emails.layouts.base')

@section('title', 'Verifica tu correo electrónico')

@section('header_title')
    Verifica tu correo electrónico
@endsection

@section('greeting')
    Hola {{ $user->name . $user->last_name }}
@endsection

@section('message_intro')
    Para completar el proceso de registro debes hacer la verificación de correo:
@endsection

@section('message_details')
    <p>
        <a href="{{ $verifyUrl }}" target="_blank"
           style="
            background-color:#2e7d5b;
            color:#ffffff;
            padding:14px 26px;
            text-decoration:none;
            font-weight:bold;
            border-radius:6px;
            display:inline-block;
            font-family:Arial, Helvetica, sans-serif;
            font-size:16px;
            "
        >Verificar mi email</a>
    </p>
    <p>
    </p>
@endsection

@section('message_footer')
    Si no creaste esta cuenta, o no solicitaste la verificación ignora este mensaje.
    <br>
    Este enlace expirará en 60 minutos.
@endsection
