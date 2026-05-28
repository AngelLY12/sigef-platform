import { Address } from "../../../core/models/domain/address.model";
import { BloodType } from "../../../core/models/types/blood-type.type";
import { Gender } from "../../../core/models/types/gender.type";

export interface EditProfileParams {
  name?: string,
  last_name?: string,
  email?: string,
  phone_number?: string,
  birthdate?: string,
  gender?: Gender,
  address?: Address,
  blood_type?: BloodType
}

export interface EditPassword {
  currentPassword: string,
  newPassword: string
}
