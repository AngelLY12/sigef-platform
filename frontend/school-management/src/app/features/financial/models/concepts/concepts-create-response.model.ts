export interface ConceptsCreateResponse {
  id: number;
  conceptName: string;
  status: string;
  appliesTo: string;
  description?: string;
  amount: string;
  startDate: string;
  endDate: string;
  affectedStudentsCount: number;
  metadata: {
    exception_count: number;
    career_count: number;
    semester_count: number;
  };
  message: string;
  createdAt: string;
}
