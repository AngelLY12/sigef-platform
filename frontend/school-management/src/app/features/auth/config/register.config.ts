export const REGISTER_STEPS = [
  'Datos personales',
  'Contacto',
  'Dirección',
  'Seguridad',
];

export const ADDRESS_CUSTOM_ERRORS = {
  cp: {
    pattern: 'El código postal debe tener 5 dígitos',
  },
  street: {
    maxlength: 'La calle no puede tener más de 100 caracteres',
  },
  number: {
    maxlength: 'El número no puede tener más de 10 caracteres',
  },
  neighborhood: {
    maxlength: 'La colonia no puede tener más de 100 caracteres',
  },
  state: {
    maxlength: 'El estado no puede tener más de 50 caracteres',
  },
  city: {
    maxlength: 'La ciudad no puede tener más de 50 caracteres',
  },
};
