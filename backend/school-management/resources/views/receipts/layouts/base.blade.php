<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Recibo de Pago - CBTA No. 71')</title>
    <meta name="receipt-id" content="@yield('receipt_id', '')">
    <meta name="receipt-folio" content="@yield('folio', '')">
    <meta name="receipt-amount" content="@yield('amount', '')">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #013237;
            --secondary: #4CA771;
            --accent: #1a4d44;
            --light-bg: #f0f2f5;
            --card-bg: #ffffff;
            --text-primary: #013237;
            --text-secondary: #4a6b63;
            --border-color: #d0e6de;
        }

        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 12px;
            color: var(--text-primary);
            min-height: 100vh;
        }

        .receipt-container {
            max-width: 700px;
            margin: 0 auto;
            background-color: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(1, 50, 55, 0.15);
        }

        .receipt-header {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            padding: clamp(20px, 5vw, 30px) clamp(16px, 4vw, 30px) clamp(16px, 3vw, 20px);
            color: white;
        }

        .receipt-title {
            text-align: left;
            border-top: 2px solid rgba(255,255,255,0.2);
            padding-top: clamp(10px, 2vw, 15px);
            margin-top: clamp(8px, 1.5vw, 10px);
        }

        @media (min-width: 480px) {
            .receipt-title {
                text-align: right;
            }
        }

        .receipt-title h1 {
            font-size: clamp(20px, 5vw, 32px);
            font-weight: 700;
            margin: 0;
            color: white;
            text-transform: uppercase;
            letter-spacing: clamp(1px, 0.3vw, 2px);
            line-height: 1.2;
        }

        .receipt-title p {
            font-size: clamp(11px, 2.5vw, 14px);
            margin: clamp(3px, 0.8vw, 5px) 0 0;
            opacity: 0.9;
            color: #d4f0e6;
        }

        @media (min-width: 768px) and (max-width: 1024px) {

            .receipt-title h1 {
                font-size: 28px;
            }
        }

        .receipt-body {
            padding: 20px 16px;
            position: relative;
            overflow: hidden;
        }

        /* Folio responsivo */
        .folio-section {
            background: linear-gradient(135deg, #f0f9f5, #e6f0ec);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            border: 1px solid var(--secondary);
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        @media (min-width: 480px) {
            .folio-section {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }

        .folio-label {
            font-size: 13px;
            color: var(--primary);
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .folio-value {
            font-size: 16px;
            color: var(--primary);
            font-weight: 700;
            background: white;
            padding: 6px 16px;
            border-radius: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            font-family: monospace;
            letter-spacing: 1px;
            word-break: break-all;
        }

        .student-card {
            background: linear-gradient(to right, #f8fcfc, #f0f7f4);
            border-left: 6px solid var(--secondary);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.02);
        }

        .student-header {
            margin-bottom: 8px;
        }

        .student-label {
            font-size: 13px;
            color: var(--secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .student-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 4px;
            line-height: 1.3;
            word-break: break-word;
        }

        .student-email {
            color: var(--secondary);
            font-size: 13px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 5px;
            word-break: break-all;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 20px;
            background: #f9fcfb;
            padding: 16px;
            border-radius: 12px;
        }

        @media (min-width: 480px) {
            .details-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .detail-item {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }

        .detail-label {
            font-size: 11px;
            color: var(--secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0 0 4px;
        }

        .detail-value {
            font-size: 15px;
            color: var(--primary);
            font-weight: 500;
            margin: 0;
            word-break: break-word;
        }

        .detail-value.strong {
            font-weight: 700;
            font-size: 16px;
        }

        .amount-box {
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 16px;
            padding: 20px 16px;
            margin: 16px 0 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 8px 20px rgba(1, 50, 55, 0.3);
        }

        @media (min-width: 480px) {
            .amount-box {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }

        .amount-label {
            color: #d4f0e6;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .amount-value {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 2px 2px 0 rgba(0,0,0,0.1);
            line-height: 1.2;
        }

        .amount-value span {
            font-size: 18px;
            font-weight: 500;
            opacity: 0.9;
        }

        .reference-box {
            background: #f0f7f4;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
            border: 1px dashed var(--secondary);
        }

        .reference-label {
            font-size: 10px;
            color: var(--secondary);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .reference-value {
            font-size: 12px;
            color: var(--primary);
            font-family: monospace;
            word-break: break-all;
        }

        .payment-details-card {
            background: #f9fcfb;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            border: 1px solid var(--border-color);
        }

        .payment-details-title {
            font-size: 13px;
            color: var(--secondary);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 6px;
        }

        .payment-details-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        @media (min-width: 480px) {
            .payment-details-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .payment-detail-item {
            font-size: 12px;
        }

        .payment-detail-item .label {
            color: var(--secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            display: block;
            margin-bottom: 2px;
        }

        .payment-detail-item .value {
            color: var(--primary);
            font-weight: 500;
            word-break: break-word;
            font-size: 13px;
        }

        .stripe-link {
            display: inline-block;
            margin-top: 12px;
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            padding: 8px 16px;
            background: #e8f4f0;
            border-radius: 30px;
            border: 1px solid var(--secondary);
            width: 100%;
            text-align: center;
        }

        .stripe-link:hover {
            background: #d0e6de;
        }

        /* Footer */
        .footer-note {
            border-top: 2px solid #e9f0ed;
            padding: 16px 0 0;
            margin-top: 20px;
            text-align: center;
        }

        .footer-note p {
            color: var(--text-secondary);
            font-size: 12px;
            margin: 4px 0;
            line-height: 1.4;
        }

        .qr-section {
            margin-top: 24px;
            text-align: center;
            border-top: 2px dashed var(--secondary);
            padding-top: 16px;
        }

        .qr-code {
            background: white;
            padding: 8px;
            display: inline-block;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .qr-text {
            margin: 8px 0 0;
            color: var(--primary);
            font-size: 12px;
        }

        .print-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            z-index: 9999;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(1, 50, 55, 0.3);
            transition: all 0.3s ease;
        }

        .print-button:hover {
            background: var(--accent);
            transform: translateY(-2px);
        }

        .watermark::after {
            content: "CBTA 71";
            position: absolute;
            bottom: 30px;
            right: 15px;
            font-size: 40px;
            font-weight: 800;
            color: rgba(76, 167, 113, 0.03);
            transform: rotate(-15deg);
            pointer-events: none;
            z-index: 0;
        }

        @media (min-width: 768px) {
            .watermark::after {
                font-size: 60px;
                right: 30px;
            }
        }

        @media (max-width: 360px) {
            body {
                padding: 8px;
            }

            .student-name {
                font-size: 18px;
            }

            .amount-value {
                font-size: 24px;
            }

            .amount-value span {
                font-size: 16px;
            }
        }

        @media print {

            @page {
                size: A4;
                margin: 12mm;
            }

            body {
                background: white !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            button {
                display: none !important;
            }

            .receipt-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                overflow: visible !important;
            }

            .receipt-header,
            .student-card,
            .details-grid,
            .amount-box,
            .payment-details-card,
            .reference-box,
            .footer-note,
            .folio-section {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .details-grid,
            .payment-details-grid {
                display: block !important;
            }

            .detail-item,
            .payment-detail-item {
                margin-bottom: 10px;
            }

            .watermark::after {
                opacity: 0.06 !important;
            }

            .amount-value {
                font-size: 32px !important;
            }

            .footer-note {
                page-break-before: avoid !important;
            }
        }

    </style>
    @stack('styles')
</head>
<body>
<div class="receipt-container">
    @include('receipts.partials.header')

    <div class="receipt-body watermark">
        @yield('content')
        @include('receipts.partials.qr')
        @include('receipts.partials.footer')
    </div>
</div>

@include('receipts.partials.print-button')
@stack('scripts')
</body>
</html>
