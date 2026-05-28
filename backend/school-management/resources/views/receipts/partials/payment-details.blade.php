@if(isset($receipt->metadata['payment_method_details']) && !empty($receipt->metadata['payment_method_details']))
    @php $details = $receipt->metadata['payment_method_details']; @endphp
    <div class="payment-details-card">
        <div class="payment-details-title">DETALLES DEL PAGO</div>
        <div class="payment-details-grid">
            @if(isset($details['type']))
                <div class="payment-detail-item">
                    <span class="label">Tipo</span>
                    <span class="value">{{ strtoupper($details['type']) }}</span>
                </div>
            @endif

            @if(isset($details['brand']))
                <div class="payment-detail-item">
                    <span class="label">Marca</span>
                    <span class="value">{{ strtoupper($details['brand']) }}</span>
                </div>
            @endif

            @if(isset($details['last4']))
                <div class="payment-detail-item">
                    <span class="label">Terminación</span>
                    <span class="value">•••• {{ $details['last4'] }}</span>
                </div>
            @endif

            @if(isset($details['funding']))
                <div class="payment-detail-item">
                    <span class="label">Tipo</span>
                    <span class="value">{{ ucfirst($details['funding']) }}</span>
                </div>
            @endif

            @if(isset($details['reference']) && isset($details['type']) && $details['type'] === 'oxxo')
                <div class="payment-detail-item">
                    <span class="label">Referencia OXXO</span>
                    <span class="value">{{ $details['reference'] }}</span>
                </div>
            @endif

            @if(isset($details['expires_after']))
                <div class="payment-detail-item">
                    <span class="label">Vence en</span>
                    <span class="value">{{ $details['expires_after'] }} horas</span>
                </div>
            @endif

            @if(isset($details['type']) && $details['type'] === 'spei')
                @if(isset($details['bank_name']))
                    <div class="payment-detail-item">
                        <span class="label">Banco</span>
                        <span class="value">{{ $details['bank_name'] }}</span>
                    </div>
                @endif

                @if(isset($details['clabe']))
                    <div class="payment-detail-item">
                        <span class="label">CLABE</span>
                        <span class="value">{{ $details['clabe'] }}</span>
                    </div>
                @endif

                @if(isset($details['reference']))
                    <div class="payment-detail-item">
                        <span class="label">Referencia SPEI</span>
                        <span class="value">{{ $details['reference'] }}</span>
                    </div>
                @endif
            @endif
        </div>

        @if(isset($receipt->metadata['stripe_receipt']) && $receipt->metadata['stripe_receipt'])
            <div style="text-align: center; margin-top: 15px;">
                <a href="{{ $receipt->metadata['stripe_receipt'] }}" target="_blank" class="stripe-link">
                    Ver comprobante en Stripe →
                </a>
            </div>
        @endif
    </div>
@endif
