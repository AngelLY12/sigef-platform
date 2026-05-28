@extends('emails.layouts.base')

@section('title', 'Invitación de vinculación')

@section('header_title')
    Invitación de vinculación
@endsection

@section('greeting')
    Hola {{ $recipientName }}
@endsection

@section('message_intro')
    Has recibido una invitación para vincularte como padre, madre o tutor.
@endsection

@section('message_details')
    <p>
        Has sido invitado a vincular tu cuenta como
        <strong>padre/madre o tutor</strong>.
    </p>

    <p>
        Para aceptar la invitación, haz clic en el siguiente enlace:
    </p>

    <p>
        <a href="{{ $acceptUrl }}" target="_blank"
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
        >
            Aceptar invitación
        </a>
    </p>

    <p>
    </p>
@endsection

@section('message_footer')
    Si no reconoces esta invitación, puedes ignorar este mensaje.
    <br>
    Este enlace expirará en 48 horas por seguridad.
@endsection
