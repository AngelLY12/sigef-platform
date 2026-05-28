import { Address } from "../../../core/models/domain/address.model";
import { BloodType } from "../../../core/models/types/blood-type.type";
import { Gender } from "../../../core/models/types/gender.type";

export interface RegisterUser {
  name: string;
  last_name: string;
  email: string;
  password: string;
  phone_number: string;
  birthdate: string;
  gender: Gender | null;
  curp: string;
  address?: Address;
  blood_type: BloodType | null;
  status: string;

}
