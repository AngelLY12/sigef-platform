import { BackendErrorCode } from "../models/types/backend-error-code.type";
import { NAVIGATION } from "../navigation/navigation.config";

export interface ErrorConfig {
  message: string;
  action?: 'redirect' | 'retry' | 'none';
  redirectTo?: string;
  shouldShow: boolean;
  logLevel: 'error' | 'warn' | 'info';
}

export const ERROR_MESSAGES: Record<BackendErrorCode, ErrorConfig> = {
  UNAUTHENTICATED: {
    message: 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente',
    action: 'redirect',
    redirectTo: NAVIGATION.auth.login,
    shouldShow: true,
    logLevel: 'warn'
  },
  ACCESS_TOKEN_EXPIRED: {
    message: '',
    action: 'none',
    shouldShow: false,
    logLevel: 'info'
  },
  FORBIDDEN: {
    message: 'No tienes permisos para realizar esta acción',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  INVALID_CREDENTIALS: {
    message: 'Correo electrónico o contraseña incorrectos',
    action: 'none',
    shouldShow: true,
    logLevel: 'error'
  },
  USER_INACTIVE: {
    message: 'Tu cuenta está inactiva. Contacta al administrador',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  INVALID_REFRESH_TOKEN: {
    message: 'Sesión inválida. Por favor, inicia sesión nuevamente',
    action: 'redirect',
    redirectTo: NAVIGATION.auth.login,
    shouldShow: true,
    logLevel: 'warn'
  },
  REFRESH_TOKEN_EXPIRED: {
    message: 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente',
    action: 'redirect',
    redirectTo: NAVIGATION.auth.login,
    shouldShow: true,
    logLevel: 'warn'
  },
  REFRESH_TOKEN_REVOKED: {
    message: 'Tu sesión ha sido revocada. Por favor, inicia sesión nuevamente',
    action: 'redirect',
    redirectTo: NAVIGATION.auth.login,
    shouldShow: true,
    logLevel: 'warn'
  },

  // === ERRORES DE USUARIO ===
  USER_NOT_FOUND: {
    message: 'Usuario no encontrado',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  USER_ALREADY_ACTIVE: {
    message: 'El usuario ya está activo',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  USER_ALREADY_DELETED: {
    message: 'El usuario ya ha sido eliminado',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  USER_ALREADY_DISABLED: {
    message: 'El usuario ya está deshabilitado',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  USER_CANNOT_BE_DISABLED: {
    message: 'No se puede deshabilitar este usuario',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  INVALID_CURRENT_PASSWORD: {
    message: 'La contraseña actual es incorrecta',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  ADMIN_ROLE_NOT_ALLOWED: {
    message: 'Rol de administrador no permitido',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  USER_ALREADY_HAVE_STUDENT_DETAIL: {
    message: 'El usuario ya tiene detalles de estudiante',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  USER_CONFLICT_STATUS: {
    message: 'Conflicto en el estado del usuario',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  USER_EXPLICITLY_EXCLUDED: {
    message: 'Usuario explícitamente excluido',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  USER_INVALID_ROLE: {
    message: 'Rol de usuario inválido',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  USER_NOT_ALLOWED: {
    message: 'Usuario no autorizado para esta acción',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  USER_CANNOT_BE_UPDATED: {
    message: 'El usuario no puede ser actualizado',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },

  // === ERRORES DE VALIDACIÓN ===
  VALIDATION_ERROR: {
    message: 'Por favor, verifica los datos ingresados',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  INVALID_ARGUMENT: {
    message: 'Algunos datos no son válidos',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  BAD_REQUEST: {
    message: 'La solicitud no pudo ser procesada',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  INVALID_INVITATION: {
    message: 'Invitación inválida',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },

  // === ERRORES DE NEGOCIO (CONCEPTOS) ===
  CONCEPT_NOT_FOUND: {
    message: 'El concepto no existe',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_ALREADY_ACTIVE: {
    message: 'El concepto ya está activo',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_ALREADY_DELETED: {
    message: 'El concepto ya ha sido eliminado',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_ALREADY_DISABLED: {
    message: 'El concepto ya está deshabilitado',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_ALREADY_FINALIZED: {
    message: 'El concepto ya está finalizado',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_EXPIRED: {
    message: 'El concepto ha expirado',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_INACTIVE: {
    message: 'El concepto no está activo',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_INVALID_AMOUNT: {
    message: 'El monto del concepto no es válido',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_END_DATE_BEFORE_START: {
    message: 'La fecha de fin no puede ser anterior a la fecha de inicio',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_START_DATE_TOO_EARLY: {
    message: 'La fecha de inicio es demasiado temprana',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_APPLIES_TO_CONFLICT: {
    message: 'Conflicto en la aplicación del concepto',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_CANNOT_BE_DISABLED: {
    message: 'No se puede deshabilitar este concepto',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_CANNOT_BE_FINALIZED: {
    message: 'No se puede finalizar este concepto',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_CANNOT_BE_UPDATED: {
    message: 'No se puede actualizar este concepto',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_CONFLICT_STATUS: {
    message: 'Conflicto en el estado del concepto',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CONCEPT_END_DATE_BEFORE_TODAY: {
    message: 'La fecha de fin no puede ser anterior a hoy',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_END_DATE_TOO_FAR: {
    message: 'La fecha de fin es demasiado lejana',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_INVALID_END_DATE: {
    message: 'Fecha de fin inválida',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_INVALID_START_DATE: {
    message: 'Fecha de inicio inválida',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_MISSING_NAME: {
    message: 'El nombre del concepto es requerido',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_NOT_STARTED: {
    message: 'El concepto no ha iniciado',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CONCEPT_START_DATE_TOO_FAR: {
    message: 'La fecha de inicio es demasiado lejana',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },

  // === ERRORES DE PAGOS ===
  PAYMENT_NOT_FOUND: {
    message: 'El pago no existe',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  PAYMENT_ALREADY_EXISTS: {
    message: 'El pago ya existe',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  PAYMENT_METHOD_NOT_FOUND: {
    message: 'Método de pago no encontrado',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  PAYMENT_METHOD_NOT_SUPPORTED: {
    message: 'Método de pago no soportado',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  PAYMENT_IS_NOT_PAID: {
    message: 'El pago no ha sido realizado',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  PAYMENT_RETRY_NOT_ALLOWED: {
    message: 'No se permite reintentar este pago',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  PAYMENT_NOTIFICATION_ERROR: {
    message: 'Error al enviar notificación de pago',
    action: 'none',
    shouldShow: true,
    logLevel: 'error'
  },
  PAYMENT_RECONCILIATION_ERROR: {
    message: 'Error en la conciliación del pago',
    action: 'none',
    shouldShow: true,
    logLevel: 'error'
  },
  CARD_ERROR: {
    message: 'Error al procesar la tarjeta',
    action: 'none',
    shouldShow: true,
    logLevel: 'error'
  },
  STRIPE_API_ERROR: {
    message: 'Error al procesar el pago. Intenta nuevamente',
    action: 'retry',
    shouldShow: true,
    logLevel: 'error'
  },
  STRIPE_GATEWAY_ERROR: {
    message: 'Error en la pasarela de pago. Intenta más tarde',
    action: 'retry',
    shouldShow: true,
    logLevel: 'error'
  },
  STRIPE_CHECKOUT_SESSION_ERROR: {
    message: 'Error al crear la sesión de pago',
    action: 'none',
    shouldShow: true,
    logLevel: 'error'
  },

  // === ERRORES DE LÍMITES ===
  RATE_LIMIT_EXCEEDED: {
    message: 'Has realizado demasiadas solicitudes. Por favor, espera unos minutos',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  TOO_MANY_REQUESTS: {
    message: 'Demasiadas solicitudes. Por favor, espera unos minutos',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },

  // === ERRORES DE SERVIDOR ===
  INTERNAL_SERVER_ERROR: {
    message: 'Error interno del servidor. Intenta más tarde',
    action: 'retry',
    shouldShow: true,
    logLevel: 'error'
  },
  SERVICE_UNAVAILABLE: {
    message: 'Servicio no disponible temporalmente. Intenta más tarde',
    action: 'retry',
    shouldShow: true,
    logLevel: 'error'
  },
  DATABASE_ERROR: {
    message: 'Error en la base de datos. Intenta más tarde',
    action: 'retry',
    shouldShow: true,
    logLevel: 'error'
  },
  PAYLOAD_TOO_LARGE: {
    message: 'El archivo es demasiado grande',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  METHOD_NOT_ALLOWED: {
    message: 'Método no permitido',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },

  // === ERRORES DE DUPLICADOS ===
  DUPLICATE_ENTRY: {
    message: 'Este registro ya existe',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  RELATION_ALREADY_EXISTS: {
    message: 'Esta relación ya existe',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },

  // === ERRORES DE IDEMPOTENCIA ===
  IDEMPOTENCY_EXISTS_EXCEPTION: {
    message: 'La operación ya fue procesada',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  IDEMPOTENCY_TIMEOUT_EXCEPTION: {
    message: 'Tiempo de espera de idempotencia agotado',
    action: 'retry',
    shouldShow: true,
    logLevel: 'warn'
  },

  // === ERRORES DE PROMOCIONES ===
  PROMOTION_ALREADY_EXECUTED: {
    message: 'La promoción ya fue ejecutada',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  PROMOTION_NOT_ALLOWED: {
    message: 'Promoción no permitida',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },

  // === ERRORES DE ESTUDIANTES ===
  STUDENTS_NOT_FOUND: {
    message: 'Estudiantes no encontrados',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  STUDENT_DETAIL_NOT_FOUND: {
    message: 'Detalles del estudiante no encontrados',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  STUDENT_PARENTS_NOT_FOUND: {
    message: 'Padres del estudiante no encontrados',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  STUDENTS_AND_EXCEPTIONS_OVERLAP: {
    message: 'Hay estudiantes y excepciones superpuestas',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  REMOVE_EXCEPTIONS_AND_EXCEPTION_STUDENT_OVERLAP: {
    message: 'Conflicto al remover excepciones',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  CAREERS_NOT_FOUND: {
    message: 'Carreras no encontradas',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  EXCEPTION_STUDENTS_NOT_FOUND: {
    message: 'Estudiantes de excepción no encontrados',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  PARENT_CHILDREN_NOT_FOUND: {
    message: 'Hijos del padre no encontrados',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  PERMISSION_NOT_FOUND: {
    message: 'Permiso no encontrado',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  PERMISSIONS_BY_USER_NOT_FOUND: {
    message: 'Permisos del usuario no encontrados',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  RECIPIENTS_NOT_FOUND: {
    message: 'Destinatarios no encontrados',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  ROLE_NOT_FOUND: {
    message: 'Rol no encontrado',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  USERS_NOT_FOUND_FOR_UPDATE: {
    message: 'Usuarios no encontrados para actualizar',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  APPLICANT_TAG_INVALID: {
    message: 'Tag del aplicante inválido',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  CAREER_SEMESTER_INVALID: {
    message: 'Semestre de carrera inválido',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },
  SEMESTERS_NOT_FOUND: {
    message: 'Semestres no encontrados',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  PAYOUT_VALIDATION: {
    message: 'Error en la validación del pago',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  REQUIRED_FOR_APPLIES_TO: {
    message: 'Campos requeridos para la aplicación',
    action: 'none',
    shouldShow: true,
    logLevel: 'info'
  },

  // === ERRORES GENÉRICOS ===
  NOT_ALLOWED: {
    message: 'Acción no permitida',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  },
  NOT_FOUND: {
    message: 'Recurso no encontrado',
    action: 'none',
    shouldShow: true,
    logLevel: 'warn'
  }
};
