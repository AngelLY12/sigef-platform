import { Address } from "../../../../core/models/domain/address/address.model";
import { BloodType } from "../../../../core/models/enums/blood-type.enum";
import { Gender } from "../../../../core/models/enums/gender.enum";
import { Status } from "../../../../core/models/enums/status.enum";

export interface CreateUserRequest {
  name: string;
  last_name: string;
  email: string;
  phone_number: string;
  birthdate: string;
  gender: Gender;
  curp: string;
  address: Address;
  blood_type: BloodType;
  registration_date: string;
  status: Status;
}
