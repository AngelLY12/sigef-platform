import { Address } from '../../../../core/models/domain/address/address.model';
import { Role } from '../../../../core/models/enums/role.enum';
import { Permission } from '../../../../core/models/types/permissions.type';

export interface UserDetails {
  userId: number;
  basicInfo: UserBasicInfo;
  roles: Role[];
  permissions: Permission[];
  studentDetail: UserStudentDetail | null;
}

export interface UserBasicInfo {
  phone_number: string;
  birthdate: string;
  age: number;
  address: Address;
}

export interface UserStudentDetail {
  nControl: string;
  semestre: number;
  group: string;
  workshop: string;
  careerName: string;
}
