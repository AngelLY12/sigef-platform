import { NonNullableFormBuilder, Validators } from "@angular/forms";
import { ageRangeValidator } from "../../auth/utils/auth-validators.utils";
import { Gender } from "../../../core/models/enums/gender.enum";
import { BloodType } from "../../../core/models/enums/blood-type.enum";

export function createRegisterForm(fb: NonNullableFormBuilder) {
  return fb.group(
    {
      name: fb.control('', [
        Validators.required,
        Validators.minLength(2),
        Validators.maxLength(50),
        Validators.pattern(/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/),
      ]),

      last_name: fb.control('', [
        Validators.required,
        Validators.minLength(2),
        Validators.maxLength(50),
        Validators.pattern(/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/),
      ]),

      email: fb.control('', [Validators.required, Validators.email]),

      phone_number: fb.control('', [
        Validators.required,
        Validators.pattern(/^\+52[0-9]{10}$/),
      ]),

      birthdate: fb.control('', [
        Validators.required,
        ageRangeValidator(10, 100),
      ]),

      gender: fb.control<Gender>('' as Gender),

      curp: fb.control('', [
        Validators.required,
        Validators.minLength(18),
        Validators.maxLength(18),
      ]),

      blood_type: fb.control<BloodType>('' as BloodType),

      address: fb.group({
        cp: ['', [Validators.pattern(/^[0-9]{5}$/)]],
        street: ['', [Validators.maxLength(100)]],
        number: ['', [Validators.maxLength(10)]],
        neighborhood: ['', [Validators.maxLength(100)]],
        state: ['', [Validators.maxLength(50)]],
        city: ['', [Validators.maxLength(50)]],
      }),
    },

  );
}
