export interface UserListItem {
  id: number;
  fullName: string;
  email: string;
  curp: string;
  status: string;
  roles_count: number;
  createdAtHuman: string;
  deletedAtHuman?: number | null;
}
