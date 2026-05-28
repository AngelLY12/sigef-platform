export interface ConceptsChangeStatusResponse {
  conceptData: {
    id: number;
    concept_name: string;
    status: string;
    amount: string;
    start_date: string;
    end_date: string;
    applies_to: string;
  };
  message: string;
  changes: [
    {
      field: string;
      old: string;
      new: string;
      type: string;
      transition_type: string;
    },
  ];
  updatedAt: string;
}
