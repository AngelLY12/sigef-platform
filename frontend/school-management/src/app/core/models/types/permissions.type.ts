export const PERMISSIONS = [
  // =========================
  // Pagos (lectura / vistas)
  // =========================
  'view.own.pending.concepts.summary',
  'view.own.paid.concepts.summary',
  'view.own.overdue.concepts.summary',
  'view.payments.summary',
  'view.cards',
  'view.payments.history',
  'view.pending.concepts',
  'view.overdue.concepts',
  'view.receipt',

  // =========================
  // Pagos (acciones)
  // =========================
  'create.setup',
  'delete.card',
  'create.payment',

  // =========================
  // Finanzas (lectura)
  // =========================
  'view.all.pending.concepts.summary',
  'view.all.students.summary',
  'view.all.paid.concepts.summary',
  'view.concepts.summary',
  'view.concepts',
  'view.debts',
  'view.payments',

  // =========================
  // Finanzas (acciones)
  // =========================
  'create.concepts',
  'update.concepts',
  'finalize.concepts',
  'disable.concepts',
  'eliminate.concepts',
  'activate.concepts',
  'validate.debt',
  'view.payments.student.summary',
  'view.stripe.payments',
  'create.payout',

  // =========================
  // Global
  // =========================
  'refresh.all.dashboard',

  // =========================
  // Administración
  // =========================
  'attach.student',
  'import.users',
  'sync.permissions',
  'view.users',
  'sync.roles',
  'activate.users',
  'disable.users',
  'delete.users',
  'view.permissions',
  'view.roles',
  'create.user',
  'view.student',
  'update.student',
  'promote.student',
] as const;

export type Permission = typeof PERMISSIONS[number];
