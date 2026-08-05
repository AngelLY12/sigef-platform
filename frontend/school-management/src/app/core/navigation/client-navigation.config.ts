export const  CLIENT_NAVIGATION = {
  dashboard: '/client/dashboard',
  concepts: '/client/concepts',
  cards: '/client/cards',
  paymentHistory: '/client/payment/history',
  paymentDetails: (id: number) => ['/client/payment', id],
  parents: '/client/parents',
  children: '/client/children'

}
