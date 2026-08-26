export enum ReconciliationDataSource {
  CHECKOUT_SESSION = 'checkout_session',
  PAYMENT_INTENT = 'payment_intent',
  CHARGE = 'charge',
}

export const ReconciliationDataSourceLabels: Record<ReconciliationDataSource, string> = {
  [ReconciliationDataSource.CHECKOUT_SESSION]: 'Sesión de Checkout',
  [ReconciliationDataSource.PAYMENT_INTENT]: 'Intención de pago',
  [ReconciliationDataSource.CHARGE]: 'Cargo',
};
