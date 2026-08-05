export type PaymentMethodDetails =
  | CardPaymentMethod
  | OxxoPaymentMethod
  | SpeiPaymentMethod
  | UnknownPaymentMethod;

export interface CardPaymentMethod {
  type: 'tarjeta';
  brand: string;
  last4: string;
  funding?: string;
}

export interface OxxoPaymentMethod {
  type: 'oxxo';
  reference?: string;
  expires_after?: string;
}

export interface SpeiPaymentMethod {
  type: 'spei';
  bank_name?: string;
  clabe?: string;
  reference?: string;
}

export interface UnknownPaymentMethod {
  type: 'unknown';
  original_type?: string;
}
