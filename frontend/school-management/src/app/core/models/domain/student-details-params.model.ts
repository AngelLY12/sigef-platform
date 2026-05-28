export interface AttachStudentDetailsParams {
  user_id: number;
  career_id: number;
  n_control: string;
  semestre: number;
  group: string;
  workshop: string;
}

export interface UpdateStudentDetailsParams {
  career_id?: number;
  group?: string;
  workshop?: string;
}

export interface StudentDetails {
  id: number;
  user_id: number;
  career_id: number;
  n_control: string;
  semestre: number;
  group: string;
  workshop: string;
}

export interface StudentDetailsToDisplay {
  nControl: string;
  semestre: number;
  group: string;
  workshop: string;
  careerName: string;
}
