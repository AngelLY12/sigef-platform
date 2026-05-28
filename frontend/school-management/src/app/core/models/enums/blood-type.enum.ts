export enum BloodType {
   O_POSITIVE = 'O+',
   O_NEGATIVE = 'O-',
   A_POSITIVE = 'A+',
   A_NEGATIVE = 'A-',
   B_POSITIVE = 'B+',
   B_NEGATIVE = 'B-',
   AB_POSITIVE = 'AB+',
   AB_NEGATIVE = 'AB-',
}

export const BloodTypeOptions = Object.values(BloodType).map(value => ({
  label: value,
  value
}));
