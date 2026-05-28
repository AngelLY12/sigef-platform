@extends('emails.layouts.base')

@section('title', 'Recuperar contraseña')

@section('header_title')
    Recuperar contraseña
@endsection

@section('greeting')
    Hola {{ $user->name . ' ' .$user->last_name }}
@endsection

@section('message_intro')
    Para restablecer tu contraseña debes ingresar al siguiente enlace:
@endsection

@section('message_details')
    <p>
        <a href="{{ $resetUrl }}" target="_blank"
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
           ">
            Restablecer mi contraseña
        </a>
    </p>

    <p>
    </p>
@endsection

@section('message_footer')
    Si no solicitaste restablecer la contraseña, puedes ignorar este mensaje.
    <br>
    Este enlace expirará en 60 minutos.
@endsection
