import { Address } from "../../../core/models/domain/address.model";
import { Role } from "../../../core/models/enums/role.enum";
import { Permission } from "../../../core/models/types/permissions.type";

export interface UserDetails {
  userId: number;
  basicInfo: {
    phone_number: string;
    birthdate: string;
    age: number;
    address: Address
  }
  roles: Role[];
  permissions: Permission[];
  studentDetail?: {
    nControl: string;
    semestre: number;
    group: string;
    workshop: string;
    careerName: string;
  }
}
