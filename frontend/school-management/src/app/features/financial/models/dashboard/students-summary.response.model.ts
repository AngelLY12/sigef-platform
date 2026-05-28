export interface StudentsSummaryResponse {
  total_students: TotalStudents
}

export interface TotalStudents {
  totalStudents: number;
  totalApplicants: number;
}
