export interface DashboardSummary {
  populationSummary: {
    total_users: number;
    active_users: number;
    inactive_users: number;
    temporal_inactive_users: number;
    deleted_users: number;
  };
  usersByRoleSummary: {
    admin: number;
    applicant: number;
    'financial-staff': number;
    parent: number;
    student: number;
    supervisor: number;
    unverified: number;
  };
  academicSummary: {
    students_total: number;
    students_with_career: number;
    students_without_career: number;
    students_without_semester: number;
    students_without_group: number;
  };
  systemAlerts: {
    users_without_role: number;
    students_without_n_control: number;
    students_without_student_details: number;
  };
  recentActivity: {
    new_users_today: number;
    new_users_this_week: number;
    new_users_this_month: number;
  };
}
