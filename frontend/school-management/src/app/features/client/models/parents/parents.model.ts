export interface Parents {
  studentId: number;
  studentName: string;
  parentsData: ParentData[];
}

export interface ParentData {
  id: number;
  fullName: string;
  relationship: string;
}
