export interface Permission {
  id: number;
  name: string;
  type: string;
  label: string;
  group: string | null;
}
