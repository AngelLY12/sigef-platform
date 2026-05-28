<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Verificar Recibo - CBTA No. 71</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>

        :root {
            --color-primary: #013237;
            --color-secondary: #4CA771;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f0f2f5 0%, #e0e7e3 100%);
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
        }

        .verification-card {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(1, 50, 55, 0.15);
            animation: slideUp 0.4s ease;
        }

        .header {
            background: linear-gradient(135deg, var(--color-secondary), var(--color-primary));
            padding: clamp(20px, 5vw, 30px) clamp(16px, 4vw, 30px) clamp(16px, 3vw, 20px);
            color: white;
            display: flex;
            align-items: center;
            flex-direction: column;
            gap: clamp(10px, 2vw, 15px);
        }

        .header-logo-container {
            display: flex;
            justify-content: flex-start;
        }

        .header-title-container {
            text-align: center;
            border-top: 2px solid rgba(255,255,255,0.2);
            padding-top: clamp(10px, 2vw, 15px);
        }

        .header h1 {
            font-size: clamp(20px, 5vw, 28px);
            font-weight: 700;
            margin: 0 0 4px 0;
            color: white;
            text-transform: uppercase;
            letter-spacing: clamp(0.5px, 0.2vw, 1px);
            line-height: 1.2;
            word-break: break-word;
        }

        .header p {
            font-size: clamp(12px, 2.5vw, 14px);
            margin: 0;
            opacity: 0.9;
            color: #d4f0e6;
            font-weight: 400;
            letter-spacing: 0.3px;
        }

        @media (max-width: 480px) {
            .header {
                padding: 16px;
            }

            .header-title-container {
                text-align: center;
            }

            .header-logo-container {
                justify-content: center;
            }
        }

        @media (max-width: 360px) {
            .header h1 {
                font-size: 20px;
            }

            .header p {
                font-size: 11px;
            }
        }

        .content {
            padding: 20px 16px;
        }

        .status-badge {
            text-align: center;
            padding: 16px 12px;
            border-radius: 16px;
            margin-bottom: 20px;
        }

        .status-badge.valid {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }

        .status-badge.invalid {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }

        .status-badge h2 {
            margin: 0 0 8px;
            font-size: 20px;
            font-weight: 600;
            word-break: break-word;
        }

        .status-badge p {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .receipt-data {
            background: #f8fcfb;
            border-radius: 16px;
            padding: 16px;
            border: 1px solid #d0e6de;
        }

        .data-row {
            display: flex;
            flex-direction: column;
            padding: 12px 0;
            border-bottom: 1px solid #e0e7e3;
            gap: 4px;
        }

        .data-row:last-child {
            border-bottom: none;
        }

        .data-label {
            color: #4CA771;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .data-value {
            color: #013237;
            font-weight: 500;
            font-size: 15px;
            line-height: 1.4;
            word-break: break-word;
        }

        .data-value.strong {
            font-weight: 700;
            font-size: 18px;
        }

        .footer {
            text-align: center;
            padding: 16px;
            background: #f0f7f4;
            border-top: 1px solid #d0e6de;
        }

        .footer small {
            color: #4a6b63;
            font-size: 12px;
            line-height: 1.6;
            display: block;
        }

        .verification-date {
            text-align: center;
            margin-top: 16px;
            color: #666;
            font-size: 12px;
        }

        @media (max-width: 360px) {
            body {
                padding: 8px;
            }

            .header h1 {
                font-size: 22px;
            }

            .data-value.strong {
                font-size: 16px;
            }

            .status-badge h2 {
                font-size: 18px;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media print {
            body { background: white; padding: 0; }
            .verification-card { box-shadow: none; max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="verification-card">
    <div class="header">
        <div class="header-logo-container">
            @include('partials.logo')
        </div>
        <div class="header-title-container">
            <h1>Verificación de Recibo</h1>
            <p>Valida la autenticidad de tu recibo</p>
        </div>
    </div>

    <div class="content">
        @if($receipt)
            <div class="status-badge valid">
                <h2>RECIBO VÁLIDO</h2>
                <p>Este recibo existe en nuestros registros oficiales</p>
            </div>

            <div class="receipt-data">
                <div class="data-row">
                    <span class="data-label">Folio</span>
                    <span class="data-value strong">{{ $receipt->folio }}</span>
                </div>

                <div class="data-row">
                    <span class="data-label">Alumno</span>
                    <span class="data-value">{{ $receipt->payer_name }}</span>
                </div>

                <div class="data-row">
                    <span class="data-label">Email</span>
                    <span class="data-value" style="font-size: 13px;">{{ $receipt->payer_email }}</span>
                </div>

                <div class="data-row">
                    <span class="data-label">Concepto</span>
                    <span class="data-value">{{ $receipt->concept_name }}</span>
                </div>

                <div class="data-row">
                    <span class="data-label">Monto pagado</span>
                    <span class="data-value strong">${{ $receipt->amount_received }} MXN</span>
                </div>

                <div class="data-row">
                    <span class="data-label">Fecha de emisión</span>
                    <span class="data-value">{{ $receipt->issued_at->format('d/m/Y H:i') }}</span>
                </div>

                @if($receipt->transaction_reference)
                    <div class="data-row">
                        <span class="data-label">Referencia</span>
                        <span class="data-value" style="font-size: 13px;">{{ $receipt->transaction_reference }}</span>
                    </div>
                @endif
            </div>

            <div class="verification-date">
                Verificado el {{ now()->format('d/m/Y H:i:s') }}
            </div>
        @else
            <div class="status-badge invalid">
                <h2>RECIBO NO VÁLIDO</h2>
                <p>El folio proporcionado no existe en nuestros registros</p>
            </div>
        @endif
    </div>

    <div class="footer">
        <small>
            Este documento es una verificación oficial<br>
            CBTA No. 71 - Todos los derechos reservados
        </small>
    </div>
</div>
</body>
</html>
