export interface ConceptUpdateResponse {
  id: number;
  conceptName: string;
  status: string;
  appliesTo: string;
  description: string;
  amount: string;
  startDate: string;
  endDate: string;
  message: string;
  updatedAt: string;
  changes: [
    {
      field: string;
      old: string;
      new: string;
      type: string;
    },
  ];
}

export interface ConceptUpdateRelationsResponse {
  status: string;
  metadata: {
    concept_name: string;
    applies_to: string;
    students_count: number;
    exception_count: number;
    career_count: number;
    semester_count: number;
    tags: [
      {
        tag_: string;
      },
    ];
  };
  message: string;
  updatedAt: string;
  changes: [
    {
      field: string;
      old?: string;
      new?: string;
      type: string;
      added?: string[];
      removed?: string[];
      note?: string;
    },
  ];
  affectedSummary: [
    {
      newlyAffectedCount: number;
      removedCount: number;
      keptCount: number;
      totalAffectedCount: number;
      previouslyAffectedCount: number;
    },
  ];
}
